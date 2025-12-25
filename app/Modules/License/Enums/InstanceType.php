<?php

declare(strict_types=1);

namespace App\Modules\License\Enums;

enum InstanceType: string
{
    case SITE = 'site';
    case DEVICE = 'device';
    case SERVER = 'server';

    /**
     * Get all possible values
     * 
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::SITE => 'Website',
            self::DEVICE => 'Device',
            self::SERVER => 'Server',
        };
    }
}