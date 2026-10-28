<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VnpaySandboxCheck extends Command
{
    protected $signature = 'payments:check-sandbox';

    protected $description = 'Check Sandbox configuration locally without printing credentials or contacting VNPAY';

    public function handle(): int
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');
        $checks = [
            'Database thử riêng, không phải dữ liệu cửa hàng' => config('database.connections.mysql.database') === 'thu_thanh_sandbox'
                || (app()->environment('testing') && config('database.connections.mysql.database') === 'thu_thanh_test'),
            'Môi trường thanh toán là Sandbox' => config('payments.vnpay.environment') === 'sandbox',
            'Đã điền mã merchant' => filled(config('payments.vnpay.merchant')),
            'Đã điền hash secret' => filled(config('payments.vnpay.secret')),
            'Đã mở thanh toán (VNPAY_DEFERRED=false)' => ! config('payments.vnpay.deferred', true),
            'URL thanh toán đúng Sandbox' => config('payments.vnpay.payment_url') === 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'URL truy vấn đúng Sandbox' => config('payments.vnpay.query_url') === 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction',
            'Return URL đúng frontend /payment/vnpay-return' => $frontend !== '' && config('payments.vnpay.return_url') === $frontend.'/payment/vnpay-return',
            'PHP hỗ trợ HTTPS' => extension_loaded('openssl'),
        ];
        foreach ($checks as $label => $passed) {
            $this->line(($passed ? '[OK] ' : '[THIẾU/SAI] ').$label);
        }
        $this->comment('Chỉ kiểm tra cấu hình. Chưa xác thực merchant, kết nối mạng hoặc giao dịch thực tế. Không in khóa bí mật.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
