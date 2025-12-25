<?php

declare(strict_types=1);

namespace App\Modules\Brand\Contracts;

use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Models\Product;

/**
 * Interface for other modules to access Brand module data
 */
interface BrandLookupServiceInterface
{
    /**
     * Find brand by public ID.
     */
    public function findBrandByPublicId(string $publicId): ?Brand;

    /**
     * Find brand by slug.
     */
    public function findBrandBySlug(string $slug): ?Brand;

    /**
     * Find brand by API key.
     */
    public function findBrandByApiKey(string $apiKey): ?Brand;

    /**
     * Find product by public ID.
     */
    public function findProductByPublicId(string $publicId): ?Product;

    /**
     * Check if product belongs to brand.
     */
    public function productBelongsToBrand(string $productPublicId, string $brandPublicId): bool;
}
