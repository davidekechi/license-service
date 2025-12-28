<?php

declare(strict_types=1);

namespace App\Modules\License\Controllers;

use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Models\LicenseKey;
use App\Modules\License\Resources\LicenseStatusResource;
use App\Modules\Shared\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LicenseStatusController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository
    ) {
    }

    /**
     * Check the status of a license key and all its licenses.
     */
    public function status(string $licenseKey): JsonResponse
    {
        try {
            // Find license key with all relationships
            $licenseKeyRecord = $this->licenseKeyRepository->findByKey($licenseKey);

            if ($licenseKeyRecord === null) {
                return ApiResponse::notFound(
                    message: 'License key not found'
                );
            }

            // Eager load relationships
            $licenseKeyRecord->load([
                'licenses.activations' => function ($query) {
                    $query->whereNull('deactivated_at'); // Only active activations
                },
            ]);

            // Update heartbeat for all active activations
            $this->updateHeartbeats($licenseKeyRecord);

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

    /**
     * Update heartbeat timestamps for all active activations.
     */
    private function updateHeartbeats(LicenseKey $licenseKey): void
    {
        try {
            foreach ($licenseKey->licenses as $license) {
                foreach ($license->activations as $activation) {
                    if ($activation->isActive()) {
                        $activation->updateHeartbeat();
                    }
                }
            }
        } catch (\Exception $e) {
            // Log but don't fail the request if heartbeat update fails
            Log::warning('Failed to update activation heartbeats', [
                'error'       => $e->getMessage(),
                'license_key' => $licenseKey->key,
            ]);
        }
    }
}
