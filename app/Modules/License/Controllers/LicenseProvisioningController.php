<?php

declare(strict_types=1);

namespace App\Modules\License\Controllers;

use App\Modules\License\DTOs\ProvisionLicenseDTO;
use App\Modules\License\Requests\ProvisionLicenseRequest;
use App\Modules\License\Resources\LicenseKeyResource;
use App\Modules\License\Services\ProvisionLicenseService;
use App\Modules\Shared\Support\Exceptions\InvalidProductException;
use App\Modules\Shared\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LicenseProvisioningController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly ProvisionLicenseService $provisionLicenseService
    ) {
    }

    /**
     * Provision a new license or add products to existing license key.
     *
     * @param ProvisionLicenseRequest $request
     * @return JsonResponse
     */
    public function provision(ProvisionLicenseRequest $request): JsonResponse
    {
        try {
            // Get authenticated brand from middleware
            $brand = $request->get('authenticated_brand');

            if ($brand === null) {
                return ApiResponse::unauthorized('Brand authentication required');
            }

            // Convert request to DTO
            $dto = ProvisionLicenseDTO::fromRequest($request);

            // Call service
            $licenseKey = $this->provisionLicenseService->provision(
                brand: $brand,
                customerEmail: $dto->customerEmail,
                products: \array_map(
                    fn ($product) => $product->toArray(),
                    $dto->products
                ),
                existingLicenseKey: $dto->licenseKey
            );

            // Return resource
            return ApiResponse::success(
                data: new LicenseKeyResource($licenseKey->load('licenses')),
                message: 'License provisioned successfully',
                statusCode: 201
            );
        } catch (InvalidProductException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                errors: null,
                statusCode: 422
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                errors: null,
                statusCode: 400
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError(
                errors: $e->errors(),
                message: $e->getMessage()
            );
        } catch (\Exception $e) {
            Log::error('Failed to provision license', [
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
                'brand_id' => $brand->public_id ?? 'unknown',
            ]);

            return ApiResponse::serverError(
                message: 'An unexpected error occurred while provisioning license'
            );
        }
    }
}
