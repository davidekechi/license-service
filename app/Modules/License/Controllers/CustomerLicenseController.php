<?php

declare(strict_types=1);

namespace App\Modules\License\Controllers;

use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Resources\LicenseKeyResource;
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
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository
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
            // Validate email format
            $validated = $request->validate([
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);

            $perPage = (int) ($validated['per_page'] ?? 20);

            // Get authenticated brand (required by middleware)
            $brand = $request->get('authenticated_brand');

            if ($brand === null) {
                return ApiResponse::unauthorized('Brand authentication required');
            }

            // Laravel automatically decodes route parameters, so we don't need to urldecode
            // Validate email format
            if (!\filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ApiResponse::validationError(
                    errors: ['email' => ['The email format is invalid']],
                    message: 'Invalid email format'
                );
            }

            // Get all license keys for this customer email
            $licenseKeys = $this->licenseKeyRepository->getByCustomerEmail($email);

            // Eager load relationships
            $licenseKeys->load(['licenses.activations' => function ($query) {
                $query->whereNull('deactivated_at');
            }]);

            // Paginate in memory (since we already have the collection)
            $currentPage = (int) $request->input('page', 1);
            $offset      = ($currentPage - 1) * $perPage;

            $paginatedKeys = $licenseKeys->slice($offset, $perPage)->values();

            $total    = $licenseKeys->count();
            $lastPage = (int) \ceil($total / $perPage);

            // Return paginated resource collection
            return ApiResponse::success(
                data: [
                    'data' => LicenseKeyResource::collection($paginatedKeys),
                    'meta' => [
                        'current_page' => $currentPage,
                        'per_page'     => $perPage,
                        'total'        => $total,
                        'last_page'    => $lastPage,
                        'from'         => $offset + 1,
                        'to'           => \min($offset + $perPage, $total),
                    ],
                ],
                message: 'Customer licenses retrieved successfully'
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
