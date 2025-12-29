<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Models\LicenseKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerLicenseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository
    ) {
    }

    /**
     * Get paginated license keys for a customer email.
     *
     * @param string $email Customer email address
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<int, LicenseKey>
     */
    public function getCustomerLicenses(string $email, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        // Validate email format
        if (!\filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Get paginated license keys from repository with eager loading
        return $this->licenseKeyRepository->paginateByCustomerEmail($email, $page, $perPage);
    }
}
