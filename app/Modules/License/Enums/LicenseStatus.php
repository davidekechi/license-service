<?php

declare(strict_types=1);

namespace App\Modules\License\Enums;

enum LicenseStatus: string
{
    case VALID     = 'valid';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
    case EXPIRED   = 'expired';

    /**
     * Get all possible values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return \array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::VALID     => 'Valid',
            self::SUSPENDED => 'Suspended',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED   => 'Expired',
        };
    }

    /**
     * Check if license is active
     */
    public function isActive(): bool
    {
        return $this === self::VALID;
    }

    /**
     * Check if license can be resumed
     */
    public function canBeResumed(): bool
    {
        return $this === self::SUSPENDED;
    }
}
