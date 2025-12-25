<?php

declare(strict_types=1);

namespace App\Modules\Brand\Contracts;

use App\Modules\Brand\Models\Brand;
use Illuminate\Database\Eloquent\Collection;

interface BrandRepositoryInterface
{
    /**
     * Find brand by ID.
     */
    public function findById(int $id): ?Brand;

    /**
     * Find brand by public ID.
     */
    public function findByPublicId(string $publicId): ?Brand;

    /**
     * Find brand by slug.
     */
    public function findBySlug(string $slug): ?Brand;

    /**
     * Find brand by API key.
     */
    public function findByApiKey(string $apiKey): ?Brand;

    /**
     * Get all active brands.
     *
     * @return Collection<int, Brand>
     */
    public function getAllActive(): Collection;

    /**
     * Create a new brand.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Brand;

    /**
     * Update a brand.
     *
     * @param array<string, mixed> $data
     */
    public function update(Brand $brand, array $data): Brand;

    /**
     * Delete a brand.
     */
    public function delete(Brand $brand): bool;
}
