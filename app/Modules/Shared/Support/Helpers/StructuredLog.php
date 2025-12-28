<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support\Helpers;

use Illuminate\Support\Facades\Log;

class StructuredLog
{
    /**
     * Log license provisioning event.
     *
     * @param array<string, mixed> $context
     */
    public static function licenseProvisioned(string $licenseKey, string $brandId, array $context = []): void
    {
        Log::info('License provisioned', \array_merge([
            'event'       => 'license_provisioned',
            'license_key' => $licenseKey,
            'brand_id'    => $brandId,
            'timestamp'   => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log license activation event.
     *
     * @param array<string, mixed> $context
     */
    public static function licenseActivated(string $licenseKey, string $instanceIdentifier, array $context = []): void
    {
        Log::info('License activated', \array_merge([
            'event'               => 'license_activated',
            'license_key'         => $licenseKey,
            'instance_identifier' => $instanceIdentifier,
            'timestamp'           => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log API request.
     *
     * @param array<string, mixed> $context
     */
    public static function apiRequest(string $method, string $path, int $statusCode, array $context = []): void
    {
        Log::info('API request', \array_merge([
            'event'       => 'api_request',
            'method'      => $method,
            'path'        => $path,
            'status_code' => $statusCode,
            'timestamp'   => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log authentication attempt.
     *
     * @param array<string, mixed> $context
     */
    public static function authenticationAttempt(bool $success, ?string $brandId = null, array $context = []): void
    {
        $level = $success ? 'info' : 'warning';

        Log::$level('Authentication attempt', \array_merge([
            'event'     => 'authentication_attempt',
            'success'   => $success,
            'brand_id'  => $brandId,
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log rate limit exceeded.
     *
     * @param array<string, mixed> $context
     */
    public static function rateLimitExceeded(string $identifier, string $limiter, array $context = []): void
    {
        Log::warning('Rate limit exceeded', \array_merge([
            'event'      => 'rate_limit_exceeded',
            'identifier' => $identifier,
            'limiter'    => $limiter,
            'timestamp'  => now()->toIso8601String(),
        ], $context));
    }
}
