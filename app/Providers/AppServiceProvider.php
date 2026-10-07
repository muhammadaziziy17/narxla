<?php

namespace App\Providers;

use Firebase\JWT\JWT;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ngrok kabi proksi ortida asl domen va HTTPS sxemasi to'g'ri aniqlanishi uchun.
        // (bootstrap/app.php'dagi withMiddleware env yuklanishidan oldin ishlaydi.)
        $trustedProxies = config('app.trusted_proxies');

        if ($trustedProxies) {
            TrustProxies::at($trustedProxies);
        }

        // id_token'larni tekshirishda soat farqiga yo'l qo'yamiz (OIDC standart amaliyoti).
        JWT::$leeway = 120;

        RateLimiter::for('valuations', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => "Juda ko'p so'rov yuborildi. Iltimos, 1 daqiqadan so'ng qayta urinib ko'ring.",
                    ], 429);
                });
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
