<?php

declare(strict_types=1);

namespace App\Modules\Shared\Middleware;

use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\Shared\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateLicenseKey
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get license key from route parameter
        $licenseKeyString = $request->route('licenseKey');

        if ($licenseKeyString === null) {
            return ApiResponse::unauthorized('License key is required');
        }

        $licenseKey = $this->licenseKeyRepository->findByKey($licenseKeyString);

        if ($licenseKey === null) {
            return ApiResponse::notFound('License key not found');
        }

        // Attach license key to request for use in controllers
        $request->merge(['validated_license_key' => $licenseKey]);

        return $next($request);
    }
}
