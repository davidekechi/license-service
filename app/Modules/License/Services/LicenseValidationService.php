<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\License\Models\License;
use App\Modules\Shared\Support\Exceptions\LicenseExpiredException;
use App\Modules\Shared\Support\Exceptions\LicenseInvalidException;

class LicenseValidationService
{
    /**
     * Validate if license can be activated.
     *
     * @throws LicenseInvalidException
     * @throws LicenseExpiredException
     */
    public function validateForActivation(License $license): void
    {
        if ($license->isCancelled()) {
            throw new LicenseInvalidException('License has been cancelled');
        }

        if ($license->isSuspended()) {
            throw new LicenseInvalidException('License is currently suspended');
        }

        if ($license->isExpired()) {
            throw new LicenseExpiredException($license->expires_at?->toDateString() ?? 'unknown date');
        }

        if (!$license->isValid()) {
            throw new LicenseInvalidException('License is not valid');
        }
    }

    /**
     * Check if license is usable (valid and not expired).
     */
    public function isUsable(License $license): bool
    {
        try {
            $this->validateForActivation($license);

            return true;
        } catch (LicenseInvalidException|LicenseExpiredException) {
            return false;
        }
    }

    /**
     * Get validation errors for a license.
     *
     * @return array<string>
     */
    public function getValidationErrors(License $license): array
    {
        $errors = [];

        if ($license->isCancelled()) {
            $errors[] = 'License has been cancelled';
        }

        if ($license->isSuspended()) {
            $errors[] = 'License is currently suspended';
        }

        if ($license->isExpired()) {
            $errors[] = 'License has expired';
        }

        if (!$license->isValid() && !$license->isSuspended() && !$license->isCancelled()) {
            $errors[] = 'License is not in a valid state';
        }

        return $errors;
    }
}
