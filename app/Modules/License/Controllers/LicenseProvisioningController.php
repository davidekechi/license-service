<?php

declare(strict_types=1);

namespace App\Modules\License\Controllers;

use App\Modules\License\DTOs\ProvisionLicenseDTO;
use App\Modules\License\Requests\ProvisionLicenseRequest;
use App\Modules\License\Resources\LicenseResource;
use App\Modules\License\Services\BrandService;
use App\Modules\License\Services\ProvisionLicenseService;
use App\Modules\Shared\Support\Exceptions\InvalidProductException;
use App\Modules\Shared\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LicenseProvisioningController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly ProvisionLicenseService $provisionLicenseService,
        private readonly BrandService $brandService
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
        $brandPublicId = null;

        try {
            // Get authenticated brand from middleware
            $brand = $request->get('authenticated_brand');

            if ($brand === null) {
                return ApiResponse::unauthorized('Brand authentication required');
            }

            $brandPublicId = $brand->public_id;

            // Convert request to DTO
            $dto = ProvisionLicenseDTO::fromRequest($request);

            // Call service (enrichment happens inside service)
            $licenseKey = $this->provisionLicenseService->provision(
                brandPublicId: $brandPublicId,
                customerEmail: $dto->customerEmail,
                products: $dto->products,
                existingLicenseKey: $dto->licenseKey
            );

            // Load licenses with relationships
            $licenseKey->load('licenses');

            // Get products for each license and prepare additional data
            $licensesWithProducts = $licenseKey->licenses->map(function ($license) {
                $product = $this->brandService->findProductByPublicId($license->product_id);

                $additionalData = [
                    'product' => $product ? [
                        'public_id' => $product->public_id,
                        'name'      => $product->name,
                        'slug'      => $product->slug,
                        'max_seats' => $product->max_seats,
                        'is_active' => $product->is_active,
                    ] : null,
                ];

                return (new LicenseResource($license))->withAdditionalData($additionalData);
            })->toArray();

            // Build response manually to include licenses with product data
            $responseData = [
                'license_key'    => $licenseKey->key,
                'customer_email' => $licenseKey->customer_email,
                'licenses'       => $licensesWithProducts,
                'created_at'     => $licenseKey->created_at->toIso8601String(),
            ];

            // Return resource
            return ApiResponse::success(
                data: $responseData,
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
                'brand_id' => $brandPublicId ?? 'unknown',
            ]);

            return ApiResponse::serverError(
                message: 'An unexpected error occurred while provisioning license: '. $e->getMessage()
            );
        }
    }
}
