<?php

declare(strict_types=1);

namespace App\Modules\License\Contracts;

use App\Modules\License\Models\LicenseKey;
use Illuminate\Database\Eloquent\Collection;

interface LicenseKeyRepositoryInterface
{
    /**
     * Find license key by ID.
     */
    public function findById(int $id): ?LicenseKey;

    /**
     * Find license key by public ID.
     */
    public function findByPublicId(string $publicId): ?LicenseKey;

    /**
     * Find license key by key string.
     */
    public function findByKey(string $key): ?LicenseKey;

    /**
     * Get all license keys for a customer email.
     *
     * @return Collection<int, LicenseKey>
     */
    public function getByCustomerEmail(string $email): Collection;

    /**
     * Get all license keys for a brand.
     *
     * @return Collection<int, LicenseKey>
     */
    public function getByBrandId(string $brandPublicId): Collection;

    /**
     * Create a new license key.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): LicenseKey;

    /**
     * Update a license key.
     *
     * @param array<string, mixed> $data
     */
    public function update(LicenseKey $licenseKey, array $data): LicenseKey;
}
