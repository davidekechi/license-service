<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Enums;

enum AuditEventType: string
{
    case LICENSE_PROVISIONED   = 'license_provisioned';
    case LICENSE_ACTIVATED     = 'license_activated';
    case LICENSE_DEACTIVATED   = 'license_deactivated';
    case LICENSE_SUSPENDED     = 'license_suspended';
    case LICENSE_RESUMED       = 'license_resumed';
    case LICENSE_CANCELLED     = 'license_cancelled';
    case LICENSE_RENEWED       = 'license_renewed';
    case LICENSE_EXPIRED       = 'license_expired';
    case LICENSE_KEY_GENERATED = 'license_key_generated';

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
            self::LICENSE_PROVISIONED   => 'License Provisioned',
            self::LICENSE_ACTIVATED     => 'License Activated',
            self::LICENSE_DEACTIVATED   => 'License Deactivated',
            self::LICENSE_SUSPENDED     => 'License Suspended',
            self::LICENSE_RESUMED       => 'License Resumed',
            self::LICENSE_CANCELLED     => 'License Cancelled',
            self::LICENSE_RENEWED       => 'License Renewed',
            self::LICENSE_EXPIRED       => 'License Expired',
            self::LICENSE_KEY_GENERATED => 'License Key Generated',
        };
    }
}
