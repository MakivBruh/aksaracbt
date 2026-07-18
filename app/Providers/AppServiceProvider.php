<?php

namespace App\Providers;

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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('peserta-login', function (Request $request) {
            return Limit::perMinute(12)->by((string) $request->ip());
        });

        RateLimiter::for('peserta-api', function (Request $request) {
            $token = $request->bearerToken() ?: $request->query('token');

            return Limit::perMinute(180)->by($token ? hash('sha256', $token) : (string) $request->ip());
        });
    }
}
