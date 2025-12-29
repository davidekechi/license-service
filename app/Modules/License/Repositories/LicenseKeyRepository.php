<?php

declare(strict_types=1);

namespace App\Modules\License\Repositories;

use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Models\LicenseKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LicenseKeyRepository implements LicenseKeyRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        private readonly LicenseKey $model
    ) {
    }

    /**
     * Find license key by ID.
     */
    public function findById(int $id): ?LicenseKey
    {
        return $this->model->find($id);
    }

    /**
     * Find license key by public ID.
     */
    public function findByPublicId(string $publicId): ?LicenseKey
    {
        return $this->model->where('public_id', $publicId)->first();
    }

    /**
     * Find license key by key string.
     */
    public function findByKey(string $key): ?LicenseKey
    {
        return $this->model->where('key', $key)->first();
    }

    /**
     * Get all license keys for a customer email.
     *
     * @return Collection<int, LicenseKey>
     */
    public function getByCustomerEmail(string $email): Collection
    {
        return $this->model->where('customer_email', $email)
            ->with(['licenses.activations'])
            ->get();
    }

    /**
     * Get paginated license keys for a customer email.
     *
     * @return LengthAwarePaginator<int, LicenseKey>
     */
    public function paginateByCustomerEmail(string $email, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->where('customer_email', $email)
            ->with(['licenses.activations' => function ($query) {
                $query->whereNull('deactivated_at');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(
                perPage: $perPage,
                page: $page
            );
    }

    /**
     * Get all license keys for a brand.
     *
     * @return Collection<int, LicenseKey>
     */
    public function getByBrandId(string $brandPublicId): Collection
    {
        return $this->model->where('brand_id', $brandPublicId)->get();
    }

    /**
     * Create a new license key.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): LicenseKey
    {
        return $this->model->create($data);
    }

    /**
     * Update a license key.
     *
     * @param array<string, mixed> $data
     */
    public function update(LicenseKey $licenseKey, array $data): LicenseKey
    {
        $licenseKey->update($data);

        return $licenseKey->fresh();
    }

    /**
     * Delete a license key (soft delete).
     */
    public function delete(LicenseKey $licenseKey): bool
    {
        return $licenseKey->delete();
    }

    /**
     * Restore a soft deleted license key.
     */
    public function restore(LicenseKey $licenseKey): bool
    {
        return $licenseKey->restore();
    }

    /**
     * Permanently delete a license key.
     */
    public function forceDelete(LicenseKey $licenseKey): bool
    {
        return $licenseKey->forceDelete();
    }
}
