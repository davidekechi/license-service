<?php

declare(strict_types=1);

namespace App\Modules\Brand\Repositories;

use App\Modules\Brand\Contracts\BrandRepositoryInterface;
use App\Modules\Brand\Models\Brand;
use Illuminate\Database\Eloquent\Collection;

class BrandRepository implements BrandRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        private readonly Brand $model
    ) {
    }

    /**
     * Find brand by ID.
     */
    public function findById(int $id): ?Brand
    {
        return $this->model->find($id);
    }

    /**
     * Find brand by public ID.
     */
    public function findByPublicId(string $publicId): ?Brand
    {
        return $this->model->where('public_id', $publicId)->first();
    }

    /**
     * Find brand by slug.
     */
    public function findBySlug(string $slug): ?Brand
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Find brand by API key.
     */
    public function findByApiKey(string $apiKey): ?Brand
    {
        return $this->model->where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all active brands.
     *
     * @return Collection<int, Brand>
     */
    public function getAllActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Create a new brand.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Brand
    {
        return $this->model->create($data);
    }

    /**
     * Update a brand.
     *
     * @param array<string, mixed> $data
     */
    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);

        return $brand->fresh();
    }

    /**
     * Delete a brand (soft delete).
     */
    public function delete(Brand $brand): bool
    {
        return $brand->delete();
    }
}
