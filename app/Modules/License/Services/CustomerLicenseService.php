<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Models\LicenseKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerLicenseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository,
        private readonly BrandService $brandService
    ) {
    }

    /**
     * Get paginated license keys for a customer email.
     *
     * @param string $email Customer email address
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<int, LicenseKey>
     */
    public function getCustomerLicenses(string $email, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        // Validate email format
        if (!\filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Get paginated license keys from repository
        $paginator = $this->licenseKeyRepository->paginateByCustomerEmail($email, $page, $perPage);

        // Enrich license keys with product data from Brand module
        $this->enrichLicenseKeysWithProducts($paginator);

        return $paginator;
    }

    /**
     * Enrich license keys with product and brand data through BrandService.
     *
     * @param LengthAwarePaginator<int, LicenseKey> $paginator
     */
    private function enrichLicenseKeysWithProducts(LengthAwarePaginator $paginator): void
    {
        // Collect all unique product IDs
        $productIds = [];
        foreach ($paginator->items() as $licenseKey) {
            foreach ($licenseKey->licenses as $license) {
                if ($license->product_id && !\in_array($license->product_id, $productIds, true)) {
                    $productIds[] = $license->product_id;
                }
            }
        }

        // Load all products at once through BrandService
        $products = [];
        foreach ($productIds as $productId) {
            $product = $this->brandService->findProductByPublicId($productId);
            if ($product !== null) {
                $products[$productId] = $product;
            }
        }

        // Attach products and brands to licenses as additional data
        foreach ($paginator->items() as $licenseKey) {
            foreach ($licenseKey->licenses as $license) {
                if (isset($products[$license->product_id])) {
                    $product = $products[$license->product_id];

                    // Store the product data in a custom property for the resource to access
                    $license->setAttribute('_product', [
                        'public_id' => $product->public_id,
                        'name'      => $product->name,
                        'slug'      => $product->slug,
                        'max_seats' => $product->max_seats,
                        'is_active' => $product->is_active,
                    ]);

                    // Store the brand data (brand is always loaded with product)
                    $license->setAttribute('_brand', [
                        'public_id' => $product->brand->public_id,
                        'name'      => $product->brand->name,
                    ]);
                }
            }
        }
    }
}
