<?php

declare(strict_types=1);

namespace App\Modules\Brand\Repositories;

use App\Modules\Brand\Contracts\ProductRepositoryInterface;
use App\Modules\Brand\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        private readonly Product $model
    ) {
    }

    /**
     * Find product by ID.
     */
    public function findById(int $id): ?Product
    {
        return $this->model->find($id);
    }

    /**
     * Find product by public ID.
     */
    public function findByPublicId(string $publicId): ?Product
    {
        return $this->model->where('public_id', $publicId)->first();
    }

    /**
     * Find product by slug for a specific brand.
     */
    public function findBySlugAndBrandId(string $slug, int $brandId): ?Product
    {
        return $this->model->where('slug', $slug)
            ->where('brand_id', $brandId)
            ->first();
    }

    /**
     * Get all products for a brand.
     *
     * @return Collection<int, Product>
     */
    public function getByBrandId(int $brandId): Collection
    {
        return $this->model->where('brand_id', $brandId)->get();
    }

    /**
     * Get active products for a brand.
     *
     * @return Collection<int, Product>
     */
    public function getActiveByBrandId(int $brandId): Collection
    {
        return $this->model->where('brand_id', $brandId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Create a new product.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    /**
     * Update a product.
     *
     * @param array<string, mixed> $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh();
    }

    /**
     * Delete a product (soft delete).
     */
    public function delete(Product $product): bool
    {
        return $product->delete();
    }
}
