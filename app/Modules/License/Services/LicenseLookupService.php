<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\License\Contracts\LicenseActivationRepositoryInterface;
use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Contracts\LicenseLookupServiceInterface;
use App\Modules\License\Contracts\LicenseRepositoryInterface;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseKey;
use Illuminate\Database\Eloquent\Collection;

class LicenseLookupService implements LicenseLookupServiceInterface
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository,
        private readonly LicenseRepositoryInterface $licenseRepository,
        private readonly LicenseActivationRepositoryInterface $activationRepository
    ) {
    }

    /**
     * Find license key by key string.
     */
    public function findLicenseKeyByKey(string $key): ?LicenseKey
    {
        return $this->licenseKeyRepository->findByKey($key);
    }

    /**
     * Get all license keys for a brand.
     *
     * @return Collection<int, LicenseKey>
     */
    public function getLicenseKeysByBrandId(string $brandPublicId): Collection
    {
        return $this->licenseKeyRepository->getByBrandId($brandPublicId);
    }

    /**
     * Get all license keys for a customer email.
     *
     * @return Collection<int, LicenseKey>
     */
    public function getLicenseKeysByCustomerEmail(string $email): Collection
    {
        return $this->licenseKeyRepository->getByCustomerEmail($email);
    }

    /**
     * Find license by public ID.
     */
    public function findLicenseByPublicId(string $publicId): ?License
    {
        return $this->licenseRepository->findByPublicId($publicId);
    }

    /**
     * Get active seat count for a license.
     */
    public function getActiveSeatCount(int $licenseId): int
    {
        return $this->activationRepository->countActiveByLicenseId($licenseId);
    }
}
