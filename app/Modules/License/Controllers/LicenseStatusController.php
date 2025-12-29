<?php

declare(strict_types=1);

namespace App\Modules\License\Controllers;

use App\Modules\License\Resources\LicenseStatusResource;
use App\Modules\License\Services\LicenseStatusService;
use App\Modules\Shared\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LicenseStatusController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly LicenseStatusService $licenseStatusService
    ) {
    }

    /**
     * Check the status of a license key and all its licenses.
     */
    public function status(string $licenseKey): JsonResponse
    {
        try {
            // Get license status from service
            $licenseKeyRecord = $this->licenseStatusService->getLicenseStatus($licenseKey);

            if ($licenseKeyRecord === null) {
                return ApiResponse::notFound(
                    message: 'License key not found'
                );
            }

            // Return comprehensive status
            return ApiResponse::success(
                data: new LicenseStatusResource($licenseKeyRecord),
                message: 'License status retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve license status', [
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
                'license_key' => $licenseKey,
            ]);

            return ApiResponse::serverError(
                message: 'An unexpected error occurred while retrieving license status'
            );
        }
    }
}
