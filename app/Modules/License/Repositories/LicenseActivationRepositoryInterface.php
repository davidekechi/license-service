<?php

declare(strict_types=1);

namespace App\Modules\License\Contracts;

use App\Modules\License\Models\LicenseActivation;
use Illuminate\Database\Eloquent\Collection;

interface LicenseActivationRepositoryInterface
{
    /**
     * Find activation by ID.
     */
    public function findById(int $id): ?LicenseActivation;

    /**
     * Find activation by public ID.
     */
    public function findByPublicId(string $publicId): ?LicenseActivation;

    /**
     * Find activation by license and instance.
     */
    public function findByLicenseAndInstance(int $licenseId, string $instanceIdentifier): ?LicenseActivation;

    /**
     * Get all activations for a license.
     *
     * @return Collection<int, LicenseActivation>
     */
    public function getByLicenseId(int $licenseId): Collection;

    /**
     * Get active activations for a license.
     *
     * @return Collection<int, LicenseActivation>
     */
    public function getActiveByLicenseId(int $licenseId): Collection;

    /**
     * Count active activations for a license.
     */
    public function countActiveByLicenseId(int $licenseId): int;

    /**
     * Create a new activation.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): LicenseActivation;

    /**
     * Update an activation.
     *
     * @param array<string, mixed> $data
     */
    public function update(LicenseActivation $activation, array $data): LicenseActivation;
}
