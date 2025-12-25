<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\License\Contracts\LicenseActivationRepositoryInterface;
use App\Modules\License\Models\License;

class SeatManagementService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly LicenseActivationRepositoryInterface $activationRepository
    ) {
    }

    /**
     * Get the number of active seats for a license.
     */
    public function getActiveSeatCount(License $license): int
    {
        return $this->activationRepository->countActiveByLicenseId($license->id);
    }

    /**
     * Get the number of available seats for a license.
     */
    public function getAvailableSeatCount(License $license): int
    {
        if ($license->hasUnlimitedActivations()) {
            return PHP_INT_MAX;
        }

        $activeCount = $this->getActiveSeatCount($license);

        return \max(0, $license->max_activations - $activeCount);
    }

    /**
     * Check if license has available seats.
     */
    public function hasAvailableSeats(License $license): bool
    {
        if ($license->hasUnlimitedActivations()) {
            return true;
        }

        return $this->getAvailableSeatCount($license) > 0;
    }

    /**
     * Get seat information.
     *
     * @return array{used: int, available: int|string, total: int|string}
     */
    public function getSeatInfo(License $license): array
    {
        $activeCount = $this->getActiveSeatCount($license);

        if ($license->hasUnlimitedActivations()) {
            return [
                'used'      => $activeCount,
                'available' => 'unlimited',
                'total'     => 'unlimited',
            ];
        }

        return [
            'used'      => $activeCount,
            'available' => \max(0, $license->max_activations - $activeCount),
            'total'     => $license->max_activations,
        ];
    }
}
