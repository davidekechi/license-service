<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Models\LicenseKey;
use Illuminate\Support\Facades\Log;

class LicenseStatusService
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
     * Get license status with all relationships and product data.
     */
    public function getLicenseStatus(string $licenseKey): ?LicenseKey
    {
        // Find license key
        $licenseKeyRecord = $this->licenseKeyRepository->findByKey($licenseKey);

        if ($licenseKeyRecord === null) {
            return null;
        }

        // Eager load relationships
        $licenseKeyRecord->load([
            'licenses.activations' => function ($query) {
                $query->whereNull('deactivated_at'); // Only active activations
            },
        ]);

        // Enrich with product data from Brand module
        $this->enrichWithProductData($licenseKeyRecord);

        // Update heartbeat for all active activations
        $this->updateHeartbeats($licenseKeyRecord);

        return $licenseKeyRecord;
    }

    /**
     * Enrich license key with product and brand data through BrandService.
     */
    private function enrichWithProductData(LicenseKey $licenseKey): void
    {
        // Collect all unique product IDs
        $productIds = [];
        foreach ($licenseKey->licenses as $license) {
            if ($license->product_id && !\in_array($license->product_id, $productIds, true)) {
                $productIds[] = $license->product_id;
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
