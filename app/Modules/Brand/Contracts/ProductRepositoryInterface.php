<?php

declare(strict_types=1);

namespace App\Modules\Brand\Contracts;

use App\Modules\Brand\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Find product by ID.
     */
    public function findById(int $id): ?Product;

    /**
     * Find product by public ID.
     */
    public function findByPublicId(string $publicId): ?Product;

    /**
     * Find product by slug for a specific brand.
     */
    public function findBySlugAndBrandId(string $slug, int $brandId): ?Product;

    /**
     * Get all products for a brand.
     *
     * @return Collection<int, Product>
     */
    public function getByBrandId(int $brandId): Collection;

    /**
     * Get active products for a brand.
     *
     * @return Collection<int, Product>
     */
    public function getActiveByBrandId(int $brandId): Collection;

    /**
     * Create a new product.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Product;

    /**
     * Update a product.
     *
     * @param array<string, mixed> $data
     */
    public function update(Product $product, array $data): Product;

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool;
}