<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Detailed health check with database connection.
     */
    public function index(): JsonResponse
    {
        $checks = [
            'application' => $this->checkApplication(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $isHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'healthy');

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'service' => 'License Service',
            'version' => 'v1.0.0',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $isHealthy ? 200 : 503);
    }

    /**
     * Check application status.
     *
     * @return array<string, mixed>
     */
    private function checkApplication(): array
    {
        try {
            return [
                'status' => 'healthy',
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check database connection.
     *
     * @return array<string, mixed>
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            
            // Test a simple query
            DB::table('brands')->limit(1)->count();

            return [
                'status' => 'healthy',
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            Log::error('Health check: Database connection failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unhealthy',
                'error' => 'Database connection failed',
            ];
        }
    }

    /**
     * Check cache system.
     *
     * @return array<string, mixed>
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test';

            cache()->put($testKey, $testValue, 10);
            $retrieved = cache()->get($testKey);
            cache()->forget($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'driver' => config('cache.default'),
                ];
            }

            return [
                'status' => 'unhealthy',
                'error' => 'Cache read/write test failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'healthy', // Cache is optional
                'driver' => config('cache.default'),
                'note' => 'Cache unavailable but not critical',
            ];
        }
    }
}