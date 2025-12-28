<?php

declare(strict_types=1);

namespace App\Modules\License\Controllers;

use App\Modules\License\DTOs\ActivateLicenseDTO;
use App\Modules\License\Requests\ActivateLicenseRequest;
use App\Modules\License\Resources\LicenseActivationResource;
use App\Modules\License\Services\ActivateLicenseService;
use App\Modules\Shared\Support\Exceptions\LicenseExpiredException;
use App\Modules\Shared\Support\Exceptions\LicenseInvalidException;
use App\Modules\Shared\Support\Exceptions\LicenseNotFoundException;
use App\Modules\Shared\Support\Exceptions\SeatLimitExceededException;
use App\Modules\Shared\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LicenseActivationController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly ActivateLicenseService $activateLicenseService
    ) {
    }

    /**
     * Activate a license on a specific instance.
     */
    public function activate(ActivateLicenseRequest $request, string $licenseKey): JsonResponse
    {
        try {
            // Convert request to DTO
            $dto = ActivateLicenseDTO::fromRequest($request);

            // Call service
            $activation = $this->activateLicenseService->activate($licenseKey, $dto);

            // Return resource
            return ApiResponse::success(
                data: new LicenseActivationResource($activation),
                message: 'License activated successfully',
                statusCode: 201
            );
        } catch (SeatLimitExceededException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                errors: null,
                statusCode: 403
            );
        } catch (LicenseExpiredException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                errors: null,
                statusCode: 403
            );
        } catch (LicenseInvalidException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                errors: null,
                statusCode: 403
            );
        } catch (LicenseNotFoundException $e) {
            return ApiResponse::notFound(
                message: $e->getMessage()
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError(
                errors: $e->errors(),
                message: $e->getMessage()
            );
        } catch (\RuntimeException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                errors: null,
                statusCode: 400
            );
        } catch (\Exception $e) {
            Log::error('Failed to activate license', [
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
                'license_key' => $licenseKey,
            ]);

            return ApiResponse::serverError(
                message: 'An unexpected error occurred while activating license'
            );
        }
    }
}
