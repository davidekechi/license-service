<?php

declare(strict_types=1);

namespace App\Modules\License\Contracts;

use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseKey;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for other modules to access License module data
 */
interface LicenseLookupServiceInterface
{
    /**
     * Find license key by key string.
     */
    public function findLicenseKeyByKey(string $key): ?LicenseKey;

    /**
     * Get all license keys for a brand.
     *
     * @return Collection<int, LicenseKey>
     */
    public function getLicenseKeysByBrandId(string $brandPublicId): Collection;

    /**
     * Get all license keys for a customer email.
     *
     * @return Collection<int, LicenseKey>
     */
    public function getLicenseKeysByCustomerEmail(string $email): Collection;

    /**
     * Find license by public ID.
     */
    public function findLicenseByPublicId(string $publicId): ?License;

    /**
     * Get active seat count for a license.
     */
    public function getActiveSeatCount(int $licenseId): int;
}
