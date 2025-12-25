<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\License\Models\License;

class LicenseValidationService
{
    /**
     * Validate if license can be activated.
     *
     * @throws \RuntimeException
     */
    public function validateForActivation(License $license): void
    {
        if ($license->isCancelled()) {
            throw new \RuntimeException('License has been cancelled');
        }

        if ($license->isSuspended()) {
            throw new \RuntimeException('License is currently suspended');
        }

        if ($license->isExpired()) {
            throw new \RuntimeException('License has expired on ' . $license->expires_at?->toDateString());
        }

        if (!$license->isValid()) {
            throw new \RuntimeException('License is not valid');
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
        } catch (\RuntimeException) {
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
