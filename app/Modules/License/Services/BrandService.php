<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\Brand\Contracts\BrandLookupServiceInterface;
use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Models\Product;

/**
 * Wrapper service for License module to access Brand module
 */
class BrandService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly BrandLookupServiceInterface $brandLookupService
    ) {
    }

    /**
     * Find brand by public ID.
     */
    public function findBrandByPublicId(string $publicId): ?Brand
    {
        return $this->brandLookupService->findBrandByPublicId($publicId);
    }

    /**
     * Find product by public ID.
     */
    public function findProductByPublicId(string $publicId): ?Product
    {
        return $this->brandLookupService->findProductByPublicId($publicId);
    }

    /**
     * Validate that products belong to a brand.
     *
     * @param array<string> $productPublicIds
     */
    public function validateProductsBelongToBrand(array $productPublicIds, string $brandPublicId): bool
    {
        foreach ($productPublicIds as $productPublicId) {
            if (!$this->brandLookupService->productBelongsToBrand($productPublicId, $brandPublicId)) {
                return false;
            }
        }

        return true;
    }
}
