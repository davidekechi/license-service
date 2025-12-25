<?php

declare(strict_types=1);

namespace App\Modules\License\Repositories;

use App\Modules\License\Contracts\LicenseRepositoryInterface;
use App\Modules\License\Models\License;
use Illuminate\Database\Eloquent\Collection;

class LicenseRepository implements LicenseRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        private readonly License $model
    ) {
    }

    /**
     * Find license by ID.
     */
    public function findById(int $id): ?License
    {
        return $this->model->find($id);
    }

    /**
     * Find license by public ID.
     */
    public function findByPublicId(string $publicId): ?License
    {
        return $this->model->where('public_id', $publicId)->first();
    }

    /**
     * Get all licenses for a license key.
     *
     * @return Collection<int, License>
     */
    public function getByLicenseKeyId(int $licenseKeyId): Collection
    {
        return $this->model->where('license_key_id', $licenseKeyId)->get();
    }

    /**
     * Find license by license key ID and product ID.
     */
    public function findByLicenseKeyAndProduct(int $licenseKeyId, string $productPublicId): ?License
    {
        return $this->model->where('license_key_id', $licenseKeyId)
            ->where('product_id', $productPublicId)
            ->first();
    }

    /**
     * Create a new license.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): License
    {
        return $this->model->create($data);
    }

    /**
     * Update a license.
     *
     * @param array<string, mixed> $data
     */
    public function update(License $license, array $data): License
    {
        $license->update($data);

        return $license->fresh();
    }

    /**
     * Delete a license (soft delete).
     */
    public function delete(License $license): bool
    {
        return $license->delete();
    }

    /**
     * Restore a soft deleted license.
     */
    public function restore(License $license): bool
    {
        return $license->restore();
    }

    /**
     * Permanently delete a license.
     */
    public function forceDelete(License $license): bool
    {
        return $license->forceDelete();
    }
}
