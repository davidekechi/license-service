<?php

declare(strict_types=1);

namespace App\Modules\License\Repositories;

use App\Modules\License\Contracts\LicenseActivationRepositoryInterface;
use App\Modules\License\Models\LicenseActivation;
use Illuminate\Database\Eloquent\Collection;

class LicenseActivationRepository implements LicenseActivationRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        private readonly LicenseActivation $model
    ) {
    }

    /**
     * Find activation by ID.
     */
    public function findById(int $id): ?LicenseActivation
    {
        return $this->model->find($id);
    }

    /**
     * Find activation by public ID.
     */
    public function findByPublicId(string $publicId): ?LicenseActivation
    {
        return $this->model->where('public_id', $publicId)->first();
    }

    /**
     * Find activation by license and instance.
     */
    public function findByLicenseAndInstance(int $licenseId, string $instanceIdentifier): ?LicenseActivation
    {
        return $this->model->where('license_id', $licenseId)
            ->where('instance_identifier', $instanceIdentifier)
            ->first();
    }

    /**
     * Get all activations for a license.
     *
     * @return Collection<int, LicenseActivation>
     */
    public function getByLicenseId(int $licenseId): Collection
    {
        return $this->model->where('license_id', $licenseId)->get();
    }

    /**
     * Get active activations for a license.
     *
     * @return Collection<int, LicenseActivation>
     */
    public function getActiveByLicenseId(int $licenseId): Collection
    {
        return $this->model->where('license_id', $licenseId)
            ->whereNull('deactivated_at')
            ->get();
    }

    /**
     * Count active activations for a license.
     */
    public function countActiveByLicenseId(int $licenseId): int
    {
        return $this->model->where('license_id', $licenseId)
            ->whereNull('deactivated_at')
            ->count();
    }

    /**
     * Create a new activation.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): LicenseActivation
    {
        return $this->model->create($data);
    }

    /**
     * Update an activation.
     *
     * @param array<string, mixed> $data
     */
    public function update(LicenseActivation $activation, array $data): LicenseActivation
    {
        $activation->update($data);

        return $activation->fresh();
    }

    /**
     * Delete an activation (soft delete).
     */
    public function delete(LicenseActivation $activation): bool
    {
        return $activation->delete();
    }

    /**
     * Restore a soft deleted activation.
     */
    public function restore(LicenseActivation $activation): bool
    {
        return $activation->restore();
    }

    /**
     * Permanently delete an activation.
     */
    public function forceDelete(LicenseActivation $activation): bool
    {
        return $activation->forceDelete();
    }
}
