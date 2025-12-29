<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use Illuminate\Support\Str;

class LicenseKeyGenerator
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository
    ) {
    }

    /**
     * Generate a unique license key for a brand.
     * Format: {BRAND_PREFIX}-{XXXX}-{XXXX}-{XXXX}
     */
    public function generate(string $brandSlug): string
    {
        $prefix      = $this->getBrandPrefix($brandSlug);
        $maxAttempts = 10;
        $attempt     = 0;

        do {
            $key    = $this->generateKey($prefix);
            $exists = $this->licenseKeyRepository->findByKey($key);
            $attempt++;

            if ($attempt >= $maxAttempts) {
                throw new \RuntimeException('Failed to generate unique license key after ' . $maxAttempts . ' attempts');
            }
        } while ($exists !== null);

        return $key;
    }

    /**
     * Generate the key with format.
     */
    private function generateKey(string $prefix): string
    {
        return \sprintf(
            '%s-%s-%s-%s',
            $prefix,
            $this->generateSegment(),
            $this->generateSegment(),
            $this->generateSegment()
        );
    }

    /**
     * Generate a random segment (4 characters).
     */
    private function generateSegment(): string
    {
        return \strtoupper(Str::random(4));
    }

    /**
     * Get brand prefix from slug.
     */
    private function getBrandPrefix(string $slug): string
    {
        return match ($slug) {
            'rankmath'  => 'RANK',
            'wp-rocket' => 'WPRO',
            'imagify'   => 'IMGY',
            'backwpup'  => 'BKWP',
            default     => \strtoupper(\substr($slug, 0, 4)),
        };
    }

    /**
     * Validate license key format.
     */
    public function isValidFormat(string $key): bool
    {
        // Format: XXXX-XXXX-XXXX-XXXX (4-4-4-4 pattern)
        return (bool) \preg_match('/^[A-Z]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key);
    }
}
