<?php

declare(strict_types=1);

use App\Modules\License\Enums\InstanceType;

test('instance type enum has all values', function () {
    $values = InstanceType::values();

    expect($values)->toHaveCount(3)
        ->and($values)->toContain('site', 'device', 'server');
});

test('instance type label returns correct string', function () {
    expect(InstanceType::SITE->label())->toBe('Website')
        ->and(InstanceType::DEVICE->label())->toBe('Device')
        ->and(InstanceType::SERVER->label())->toBe('Server');
});
