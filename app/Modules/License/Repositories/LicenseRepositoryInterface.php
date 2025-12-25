<?php

declare(strict_types=1);

namespace App\Modules\License\Contracts;

use App\Modules\License\Models\License;
use Illuminate\Database\Eloquent\Collection;

interface LicenseRepositoryInterface
{
    /**
     * Find license by ID.
     */
    public function findById(int $id): ?License;

    /**
     * Find license by public ID.
     */
    public function findByPublicId(string $publicId): ?License;

    /**
     * Get all licenses for a license key.
     *
     * @return Collection<int, License>
     */
    public function getByLicenseKeyId(int $licenseKeyId): Collection;

    /**
     * Find license by license key ID and product ID.
     */
    public function findByLicenseKeyAndProduct(int $licenseKeyId, string $productPublicId): ?License;

    /**
     * Create a new license.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): License;

    /**
     * Update a license.
     *
     * @param array<string, mixed> $data
     */
    public function update(License $license, array $data): License;

    /**
     * Delete a license (soft delete).
     */
    public function delete(License $license): bool;
}