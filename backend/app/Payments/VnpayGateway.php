<?php

namespace App\Payments;

use App\Exceptions\BusinessException;
use App\Models\PaymentAttempt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class VnpayGateway implements PaymentGateway
{
    public function assertAvailable(bool $inStoreQr = false): void
    {
        if ($inStoreQr) {
            throw new BusinessException('QR_CAPABILITY_UNAVAILABLE', 'Merchant-specific pickup QR contract and adapter must be verified.', 503);
        }
        foreach (['merchant', 'secret', 'payment_url', 'return_url'] as $key) {
            if (blank(config('payments.vnpay.'.$key))) {
                throw new BusinessException('GATEWAY_NOT_CONFIGURED', 'VNPAY Sandbox merchant configuration is missing.', 503);
            }
        }
        if (config('payments.vnpay.environment') !== 'sandbox') {
            throw new BusinessException('PAYMENT_ENV_NOT_AUTHORIZED', 'Only Sandbox is enabled for this build.', 503);
        }
    }

    public function sign(array $payload): string
    {
        unset($payload['vnp_SecureHash'], $payload['vnp_SecureHashType']);
        ksort($payload, SORT_STRING);

        return hash_hmac('sha512', http_build_query($payload, '', '&', PHP_QUERY_RFC1738), (string) config('payments.vnpay.secret'));
    }

    public function paymentUrl(PaymentAttempt $attempt, string $ip): string
    {
        $this->assertAvailable();
        $payload = [
            'vnp_Version' => '2.1.0', 'vnp_Command' => 'pay', 'vnp_TmnCode' => config('payments.vnpay.merchant'),
            'vnp_Amount' => $attempt->requested_amount * 100, 'vnp_CurrCode' => 'VND', 'vnp_TxnRef' => $attempt->provider_reference,
            'vnp_OrderInfo' => 'Thu Thanh '.$attempt->provider_reference, 'vnp_OrderType' => 'other', 'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => config('payments.vnpay.return_url'), 'vnp_IpAddr' => $ip,
            'vnp_CreateDate' => $attempt->created_at->copy()->timezone('Asia/Ho_Chi_Minh')->format('YmdHis'),
            'vnp_ExpireDate' => $attempt->expires_at->timezone('Asia/Ho_Chi_Minh')->format('YmdHis'),
        ];
        $signature = $this->sign($payload);
        ksort($payload, SORT_STRING);

        return config('payments.vnpay.payment_url').'?'.http_build_query($payload, '', '&', PHP_QUERY_RFC1738).'&vnp_SecureHash='.$signature;
    }

    public function verifyCallback(array $payload): ?VerifiedPayment
    {
        $payload = array_filter($payload, fn ($key) => str_starts_with($key, 'vnp_'), ARRAY_FILTER_USE_KEY);
        if (blank(config('payments.vnpay.secret')) || blank(config('payments.vnpay.merchant'))) {
            return null;
        }
        foreach ($payload as $value) {
            if (! is_scalar($value) || strlen((string) $value) > 1000) {
                return null;
            }
        }
        $signature = $payload['vnp_SecureHash'] ?? '';
        if (! is_string($signature) || ! preg_match('/^[a-fA-F0-9]{128}$/D', $signature)
            || ! hash_equals($this->sign($payload), strtolower($signature))
            || ($payload['vnp_TmnCode'] ?? null) !== config('payments.vnpay.merchant')
            || (isset($payload['vnp_CurrCode']) && $payload['vnp_CurrCode'] !== 'VND')) {
            return null;
        }

        return $this->normalize($payload);
    }

    public function query(PaymentAttempt $attempt): ?VerifiedPayment
    {
        $this->assertAvailable();
        if (blank(config('payments.vnpay.query_url'))) {
            return null;
        }
        $payload = [
            'vnp_RequestId' => str_replace('-', '', (string) Str::uuid()), 'vnp_Version' => '2.1.0', 'vnp_Command' => 'querydr',
            'vnp_TmnCode' => config('payments.vnpay.merchant'), 'vnp_TxnRef' => $attempt->provider_reference,
            'vnp_TransactionDate' => $attempt->created_at->copy()->timezone('Asia/Ho_Chi_Minh')->format('YmdHis'),
            'vnp_CreateDate' => now('Asia/Ho_Chi_Minh')->format('YmdHis'), 'vnp_IpAddr' => config('payments.vnpay.query_ip'),
            'vnp_OrderInfo' => 'Query '.$attempt->provider_reference,
        ];
        $payload['vnp_SecureHash'] = $this->pipeSignature($payload, ['vnp_RequestId', 'vnp_Version', 'vnp_Command', 'vnp_TmnCode', 'vnp_TxnRef', 'vnp_TransactionDate', 'vnp_CreateDate', 'vnp_IpAddr', 'vnp_OrderInfo']);
        try {
            $response = Http::connectTimeout(3)->timeout(8)->post(config('payments.vnpay.query_url'), $payload);
            if (! $response->successful() || ! is_array($result = $response->json())) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }
        foreach ($result as $value) {
            if (! is_scalar($value) && $value !== null) {
                return null;
            }
        }
        $fields = ['vnp_ResponseId', 'vnp_Command', 'vnp_ResponseCode', 'vnp_Message', 'vnp_TmnCode', 'vnp_TxnRef', 'vnp_Amount', 'vnp_BankCode', 'vnp_PayDate', 'vnp_TransactionNo', 'vnp_TransactionType', 'vnp_TransactionStatus', 'vnp_OrderInfo', 'vnp_PromotionCode', 'vnp_PromotionAmount'];
        if (($result['vnp_Command'] ?? '') !== 'querydr' || ($result['vnp_ResponseCode'] ?? '') !== '00'
            || ($result['vnp_TmnCode'] ?? '') !== config('payments.vnpay.merchant') || ($result['vnp_TxnRef'] ?? '') !== $attempt->provider_reference
            || ! hash_equals($this->pipeSignature($result, $fields), (string) ($result['vnp_SecureHash'] ?? ''))
            || ($result['vnp_TransactionType'] ?? '') !== '01') {
            return null;
        }

        return $this->normalize($result);
    }

    private function pipeSignature(array $payload, array $fields): string
    {
        return hash_hmac('sha512', implode('|', array_map(fn ($field) => $payload[$field] ?? '', $fields)), (string) config('payments.vnpay.secret'));
    }

    private function normalize(array $payload): ?VerifiedPayment
    {
        $reference = (string) ($payload['vnp_TxnRef'] ?? '');
        $amount = (string) ($payload['vnp_Amount'] ?? '');
        if (! preg_match('/^[a-zA-Z0-9_-]{1,100}$/D', $reference) || ! preg_match('/^[0-9]{1,12}$/D', $amount) || (int) $amount % 100 !== 0) {
            return null;
        }
        $transactionId = (string) ($payload['vnp_TransactionNo'] ?? '');
        $status = $payload['vnp_TransactionStatus'] ?? '';
        $state = ($payload['vnp_ResponseCode'] ?? '') === '00' && $status === '00' ? 'succeeded' : ($status === '02' ? 'failed' : 'unknown');
        $paidAt = null;
        if (preg_match('/^[0-9]{14}$/D', (string) ($payload['vnp_PayDate'] ?? ''))) {
            try {
                $date = CarbonImmutable::createFromFormat('!YmdHis', $payload['vnp_PayDate'], 'Asia/Ho_Chi_Minh');
                if ($date->format('YmdHis') === $payload['vnp_PayDate']) {
                    $paidAt = $date->utc();
                }
            } catch (Throwable) {
                // A signed success with missing/invalid time is reconciled, never auto-allocated.
            }
        }
        if ($state === 'succeeded' && (! preg_match('/^[0-9]{1,15}$/D', $transactionId) || (int) $transactionId === 0)) {
            return null;
        }
        $safe = array_intersect_key($payload, array_flip(['vnp_TxnRef', 'vnp_Amount', 'vnp_TransactionNo', 'vnp_PayDate', 'vnp_ResponseCode', 'vnp_TransactionStatus']));
        ksort($safe, SORT_STRING);

        return new VerifiedPayment($reference, $state, intdiv((int) $amount, 100), $transactionId ?: null, $paidAt, $safe, hash('sha256', json_encode($safe, JSON_THROW_ON_ERROR)));
    }
}
