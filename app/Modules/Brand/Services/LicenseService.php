<?php

declare(strict_types=1);

namespace App\Modules\Brand\Services;

use App\Modules\License\Contracts\LicenseLookupServiceInterface;
use App\Modules\License\Models\LicenseKey;
use Illuminate\Database\Eloquent\Collection;

/**
 * Wrapper service for Brand module to access License module
 */
class LicenseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly LicenseLookupServiceInterface $licenseLookupService
    ) {
    }

    /**
     * Get all license keys for a brand.
     *
     * @return Collection<int, LicenseKey>
     */
    public function getLicenseKeysByBrandId(string $brandPublicId): Collection
    {
        return $this->licenseLookupService->getLicenseKeysByBrandId($brandPublicId);
    }

    /**
     * Get all license keys for a customer email.
     *
     * @return Collection<int, LicenseKey>
     */
    public function getLicenseKeysByCustomerEmail(string $email): Collection
    {
        return $this->licenseLookupService->getLicenseKeysByCustomerEmail($email);
    }
}
