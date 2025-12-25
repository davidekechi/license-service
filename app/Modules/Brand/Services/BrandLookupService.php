<?php

declare(strict_types=1);

namespace App\Modules\Brand\Services;

use App\Modules\Brand\Contracts\BrandLookupServiceInterface;
use App\Modules\Brand\Contracts\BrandRepositoryInterface;
use App\Modules\Brand\Contracts\ProductRepositoryInterface;
use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Models\Product;

class BrandLookupService implements BrandLookupServiceInterface
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository,
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Find brand by public ID.
     */
    public function findBrandByPublicId(string $publicId): ?Brand
    {
        return $this->brandRepository->findByPublicId($publicId);
    }

    /**
     * Find brand by slug.
     */
    public function findBrandBySlug(string $slug): ?Brand
    {
        return $this->brandRepository->findBySlug($slug);
    }

    /**
     * Find brand by API key.
     */
    public function findBrandByApiKey(string $apiKey): ?Brand
    {
        return $this->brandRepository->findByApiKey($apiKey);
    }

    /**
     * Find product by public ID.
     */
    public function findProductByPublicId(string $publicId): ?Product
    {
        return $this->productRepository->findByPublicId($publicId);
    }

    /**
     * Check if product belongs to brand.
     */
    public function productBelongsToBrand(string $productPublicId, string $brandPublicId): bool
    {
        $product = $this->findProductByPublicId($productPublicId);

        if ($product === null) {
            return false;
        }

        return $product->brand->public_id === $brandPublicId;
    }
}
