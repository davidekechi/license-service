<?php

declare(strict_types=1);

namespace App\Modules\License\Controllers;

use App\Modules\License\Resources\LicenseKeyResource;
use App\Modules\License\Services\CustomerLicenseService;
use App\Modules\Shared\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerLicenseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly CustomerLicenseService $customerLicenseService
    ) {
    }

    /**
     * List all licenses for a customer email across all brands.
     *
     * This endpoint is brand-authenticated and returns all licenses
     * for the specified customer email, regardless of which brand they belong to.
     */
    public function listByEmail(Request $request, string $email): JsonResponse
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page'     => ['nullable', 'integer', 'min:1'],
            ]);

            // Get authenticated brand (required by middleware)
            $brand = $request->get('authenticated_brand');

            if ($brand === null) {
                return ApiResponse::unauthorized('Brand authentication required');
            }

            $page    = (int) ($validated['page'] ?? 1);
            $perPage = (int) ($validated['per_page'] ?? 20);

            // Get paginated license keys from service
            $paginator = $this->customerLicenseService->getCustomerLicenses($email, $page, $perPage);

            // Return paginated resource collection
            return ApiResponse::success(
                data: [
                    'data' => LicenseKeyResource::collection($paginator->items()),
                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'per_page'     => $paginator->perPage(),
                        'total'        => $paginator->total(),
                        'last_page'    => $paginator->lastPage(),
                        'from'         => $paginator->firstItem(),
                        'to'           => $paginator->lastItem(),
                    ],
                ],
                message: 'Customer licenses retrieved successfully'
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::validationError(
                errors: ['email' => [$e->getMessage()]],
                message: 'Invalid email format'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError(
                errors: $e->errors(),
                message: $e->getMessage()
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve customer licenses', [
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
                'email'    => $email,
                'brand_id' => $brand->public_id ?? 'unknown',
            ]);

            return ApiResponse::serverError(
                message: 'An unexpected error occurred while retrieving customer licenses'
            );
        }
    }
}
