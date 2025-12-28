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
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for the application.
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limit (per IP)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('variables.rate_limit.api'))
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'statusCode' => 429,
                        'success' => false,
                        'message' => 'Too many requests. Please try again later.',
                        'errors' => null,
                    ], 429);
                });
        });

        // Brand-authenticated endpoints (higher limit)
        RateLimiter::for('brand-api', function (Request $request) {
            $brand = $request->get('authenticated_brand');
            $key = $brand ? $brand->public_id : $request->ip();

            return Limit::perMinute(config('variables.rate_limit.brand_api'))
                ->by($key)
                ->response(function () {
                    return response()->json([
                        'statusCode' => 429,
                        'success' => false,
                        'message' => 'Too many requests. Please try again later.',
                        'errors' => null,
                    ], 429);
                });
        });

        // Public endpoints (license status, activation) - lower limit
        RateLimiter::for('public-api', function (Request $request) {
            return Limit::perMinute(config('variables.rate_limit.public_api'))
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'statusCode' => 429,
                        'success' => false,
                        'message' => 'Too many requests. Please try again later.',
                        'errors' => null,
                    ], 429);
                });
        });

        // Activation endpoint (more generous for legitimate use)
        RateLimiter::for('activation', function (Request $request) {
            return Limit::perMinute(config('variables.rate_limit.activation'))
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'statusCode' => 429,
                        'success' => false,
                        'message' => 'Too many activation attempts. Please try again later.',
                        'errors' => null,
                    ], 429);
                });
        });
    }
}