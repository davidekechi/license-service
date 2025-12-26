<?php

declare(strict_types=1);

use App\Modules\License\Enums\LicenseStatus;

test('license status enum has all values', function () {
    $values = LicenseStatus::values();

    expect($values)->toHaveCount(4)
        ->and($values)->toContain('valid', 'suspended', 'cancelled', 'expired');
});

test('license status label returns correct string', function () {
    expect(LicenseStatus::VALID->label())->toBe('Valid')
        ->and(LicenseStatus::SUSPENDED->label())->toBe('Suspended')
        ->and(LicenseStatus::CANCELLED->label())->toBe('Cancelled')
        ->and(LicenseStatus::EXPIRED->label())->toBe('Expired');
});

test('license status isActive returns correct boolean', function () {
    expect(LicenseStatus::VALID->isActive())->toBeTrue()
        ->and(LicenseStatus::SUSPENDED->isActive())->toBeFalse()
        ->and(LicenseStatus::CANCELLED->isActive())->toBeFalse()
        ->and(LicenseStatus::EXPIRED->isActive())->toBeFalse();
});

test('license status canBeResumed returns correct boolean', function () {
    expect(LicenseStatus::SUSPENDED->canBeResumed())->toBeTrue()
        ->and(LicenseStatus::VALID->canBeResumed())->toBeFalse()
        ->and(LicenseStatus::CANCELLED->canBeResumed())->toBeFalse()
        ->and(LicenseStatus::EXPIRED->canBeResumed())->toBeFalse();
});
