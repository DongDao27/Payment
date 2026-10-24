<?php

namespace App\Providers;

use App\Actions\Auth\NormalizeIdentity;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\ProductImage;
use App\Observers\StorefrontObserver;
use App\Payments\PaymentGateway;
use App\Payments\VnpayGateway;
use App\Support\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, VnpayGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Product::class, Category::class, ProductImage::class, Order::class, PaymentReceipt::class] as $model) {
            $model::observe(StorefrontObserver::class);
        }
        RateLimiter::for('login', function (Request $request) {
            $identifier = $request->input('identifier');
            $identifier = is_string($identifier) ? $identifier : '';
            $normalized = str_contains($identifier, '@') ? NormalizeIdentity::email($identifier) : NormalizeIdentity::phone($identifier);
            $response = fn () => ApiResponse::error('TOO_MANY_REQUESTS', 'Too many attempts. Try again later.', 429);

            return [
                Limit::perMinute(30)->by('login-ip:'.$request->ip())->response($response),
                Limit::perMinute(5)->by('login:'.hash('sha256', $normalized.'|'.$request->ip()))->response($response),
            ];
        });
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by('register:'.$request->ip()));
        RateLimiter::for('password-recovery', function (Request $request) {
            $email = $request->input('email');
            $email = is_string($email) ? NormalizeIdentity::email($email) : '';

            return [
                Limit::perMinute(10)->by('recovery-ip:'.$request->ip()),
                Limit::perMinute(5)->by('recovery:'.hash('sha256', $email.'|'.$request->ip())),
            ];
        });
    }
}
