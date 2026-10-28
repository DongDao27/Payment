<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\NewsletterController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PasswordRecoveryController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\VnpayController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

Route::get('v1/payments/vnpay/ipn', [VnpayController::class, 'ipn']);
Route::get('v1/payments/vnpay/return', [VnpayController::class, 'returned'])->middleware('throttle:30,1');

// These public reads never start or write a session. Personalized home remains private.
Route::get('v1/storefront', [ProductController::class, 'storefront']);
Route::prefix('v1')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
});

// Explicit web middleware ensures CSRF/session protection even when Origin is absent.
Route::prefix('v1')->middleware(['web', AuthenticateSession::class])->group(function () {
    Route::post('newsletter/subscriptions', NewsletterController::class)->middleware('throttle:10,1');
    Route::get('home', [ProductController::class, 'home']);
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/forgot-password', [PasswordRecoveryController::class, 'requestLink'])->middleware('throttle:password-recovery');
    Route::post('auth/reset-password', [PasswordRecoveryController::class, 'reset'])->middleware('throttle:password-recovery');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::patch('auth/profile', [AuthController::class, 'updateProfile'])->middleware('throttle:10,1');
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::middleware('role:customer')->group(function () {
            Route::get('cart', [CartController::class, 'index']);
            Route::post('cart/items', [CartController::class, 'store']);
            Route::patch('cart/items/{item}', [CartController::class, 'update']);
            Route::delete('cart/items/{item}', [CartController::class, 'destroy']);
            Route::post('checkout/quotes', [QuoteController::class, 'store']);
            Route::post('orders', [OrderController::class, 'store']);
            Route::get('orders', [OrderController::class, 'index']);
            Route::get('orders/{order}', [OrderController::class, 'show']);
            Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
            Route::post('orders/{order}/reorder', [OrderController::class, 'reorder']);
            Route::post('orders/{order}/payment-attempts', [PaymentController::class, 'store'])->middleware('throttle:30,1');
            Route::get('orders/{order}/payment-status', [PaymentController::class, 'show'])->middleware('throttle:60,1');
        });
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::get('dashboard', Admin\DashboardController::class);
            Route::get('revenue', [Admin\RevenueController::class, 'index']);
            Route::post('orders/{order}/refund-records', [Admin\RefundController::class, 'store']);
            Route::get('refund-records/{refund}/evidence', [Admin\RefundController::class, 'evidence']);
            Route::get('orders', [Admin\OrderController::class, 'index']);
            Route::get('orders/{order}', [Admin\OrderController::class, 'show']);
            Route::get('orders/{order}/history', [Admin\OrderController::class, 'history']);
            Route::patch('orders/{order}/fulfillment', [Admin\OrderController::class, 'update']);
            Route::get('reconciliation-cases', [Admin\ReconciliationController::class, 'index']);
            Route::patch('reconciliation-cases/{case}', [Admin\ReconciliationController::class, 'update']);
            Route::post('orders/{order}/payment-attempts', [PaymentController::class, 'store'])->middleware('throttle:30,1');
            Route::get('orders/{order}/payment-status', [PaymentController::class, 'show'])->middleware('throttle:60,1');
            Route::get('inventory', [Admin\InventoryController::class, 'index']);
            Route::get('inventory/{product}/movements', [Admin\InventoryController::class, 'movements']);
            Route::post('inventory/{product}/adjustments', [Admin\InventoryController::class, 'store']);
            Route::get('categories', [Admin\CategoryController::class, 'index']);
            Route::post('categories', [Admin\CategoryController::class, 'store']);
            Route::patch('categories/{category}', [Admin\CategoryController::class, 'update']);
            Route::get('products', [Admin\ProductController::class, 'index']);
            Route::post('products', [Admin\ProductController::class, 'store']);
            Route::get('products/{product}', [Admin\ProductController::class, 'show']);
            Route::patch('products/{product}', [Admin\ProductController::class, 'update']);
            Route::delete('products/{product}', [Admin\ProductController::class, 'destroy']);
            Route::post('products/{product}/images', [Admin\ProductImageController::class, 'store']);
            Route::delete('products/{product}/images/{image}', [Admin\ProductImageController::class, 'destroy'])->scopeBindings();
        });
    });
});
