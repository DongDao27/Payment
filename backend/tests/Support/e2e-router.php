<?php

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Payments\VnpayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

$app = require __DIR__.'/e2e-bootstrap.php';
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$gateway = $app->make(VnpayGateway::class);

if ($uri === '/__e2e/evidence') {
    $order = Order::where('order_code', $_GET['order_code'] ?? '')->firstOrFail();
    header('Content-Type: application/json');
    echo json_encode(['payment_status' => $order->payment_status, 'receipts' => $order->receipts()->count(),
        'attempts' => $order->attempts()->count(), 'stock' => Product::whereIn('id', $order->items()->pluck('product_id'))->get(['id', 'stock_on_hand', 'stock_reserved'])->toArray()]);
    exit;
}

// Emulator is confined to this guarded router, never an application route.
if ($uri === '/__e2e/provider') {
    $attempt = PaymentAttempt::where('provider_reference', $_GET['vnp_TxnRef'] ?? '')->firstOrFail();
    $signature = $_GET['vnp_SecureHash'] ?? '';
    if (! hash_equals($gateway->sign($_GET), $signature)) {
        http_response_code(403);
        exit('Invalid test payment signature');
    }
    $reference = htmlspecialchars($attempt->provider_reference, ENT_QUOTES);
    echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Provider emulator E2E</title>';
    echo '<h1>Cổng kiểm thử tự động</h1><p>Provider emulator, không phải giao dịch VNPAY thật.</p>';
    echo '<form action="/__e2e/finish"><input type="hidden" name="reference" value="'.$reference.'">';
    echo '<button name="outcome" value="success">Xác nhận thành công</button> ';
    echo '<button name="outcome" value="cancel">Hủy thanh toán</button> ';
    echo '<button name="outcome" value="unknown">Chưa xác định</button></form></html>';
    exit;
}
if ($uri === '/__e2e/finish') {
    $attempt = PaymentAttempt::where('provider_reference', $_GET['reference'] ?? '')->firstOrFail();
    $success = ($_GET['outcome'] ?? '') === 'success';
    $unknown = ($_GET['outcome'] ?? '') === 'unknown';
    $payload = ['vnp_TmnCode' => config('payments.vnpay.merchant'), 'vnp_TxnRef' => $attempt->provider_reference,
        'vnp_Amount' => (string) ($attempt->requested_amount * 100), 'vnp_TransactionNo' => (string) (900000 + $attempt->order_id),
        'vnp_ResponseCode' => $success ? '00' : ($unknown ? '99' : '24'),
        'vnp_TransactionStatus' => $success ? '00' : ($unknown ? '01' : '02'),
        'vnp_PayDate' => now('Asia/Ho_Chi_Minh')->format('YmdHis')];
    Cache::put('provider:'.$attempt->provider_reference, $unknown ? 'unknown' : $payload, 1800);
    $payload['vnp_SecureHash'] = $gateway->sign($payload);
    header('Location: '.config('payments.vnpay.return_url').'?'.http_build_query($payload));
    exit;
}
if (str_starts_with($uri, '/storage/')) {
    $base = realpath($storage.'/app/public');
    $file = realpath($base.'/'.substr($uri, 9));
    if (! $file || ! str_starts_with($file, $base.DIRECTORY_SEPARATOR) || ! is_file($file)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: '.mime_content_type($file));
    readfile($file);
    exit;
}
Http::preventStrayRequests();
Http::fake(['https://e2e-provider.invalid/query' => function ($request) {
    $payload = Cache::get('provider:'.$request['vnp_TxnRef']);
    if (! is_array($payload)) {
        return Http::response(['vnp_ResponseCode' => '99'], 200);
    }
    $response = ['vnp_ResponseId' => 'e2e-query', 'vnp_Command' => 'querydr', 'vnp_ResponseCode' => '00',
        'vnp_Message' => 'E2E provider response', 'vnp_TmnCode' => config('payments.vnpay.merchant'),
        'vnp_TxnRef' => $payload['vnp_TxnRef'], 'vnp_Amount' => $payload['vnp_Amount'], 'vnp_BankCode' => 'NCB',
        'vnp_PayDate' => $payload['vnp_PayDate'], 'vnp_TransactionNo' => $payload['vnp_TransactionNo'],
        'vnp_TransactionType' => '01', 'vnp_TransactionStatus' => $payload['vnp_TransactionStatus'],
        'vnp_OrderInfo' => 'E2E query', 'vnp_PromotionCode' => '', 'vnp_PromotionAmount' => ''];
    $response['vnp_SecureHash'] = hash_hmac('sha512', implode('|', array_values($response)), config('payments.vnpay.secret'));

    return Http::response($response);
}]);
$app->handleRequest(Request::capture());
