<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Enums\InstanceType;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseActivation;
use App\Modules\License\Models\LicenseKey;

test('license activation can be created', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $product->public_id,
    ]);

    $activation = LicenseActivation::create([
        'license_id'          => $license->id,
        'instance_identifier' => 'https://example.com',
        'instance_type'       => InstanceType::SITE,
        'activated_at'        => now(),
    ]);

    expect($activation)->toBeModel(LicenseActivation::class)
        ->and($activation->instance_identifier)->toBe('https://example.com')
        ->and($activation->instance_type)->toBe(InstanceType::SITE)
        ->and($activation)->toHaveUlid();
});

test('activation isActive method works correctly', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $product->public_id,
    ]);

    $activeActivation = LicenseActivation::create([
        'license_id'          => $license->id,
        'instance_identifier' => 'https://active.com',
        'instance_type'       => InstanceType::SITE,
        'activated_at'        => now(),
    ]);

    $deactivatedActivation = LicenseActivation::create([
        'license_id'          => $license->id,
        'instance_identifier' => 'https://inactive.com',
        'instance_type'       => InstanceType::SITE,
        'activated_at'        => now(),
        'deactivated_at'      => now(),
    ]);

    expect($activeActivation->isActive())->toBeTrue()
        ->and($deactivatedActivation->isActive())->toBeFalse();
});

test('activation deactivate method sets timestamp', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $product->public_id,
    ]);

    $activation = LicenseActivation::create([
        'license_id'          => $license->id,
        'instance_identifier' => 'https://example.com',
        'instance_type'       => InstanceType::SITE,
        'activated_at'        => now(),
    ]);

    $activation->deactivate();

    expect($activation->deactivated_at)->not->toBeNull()
        ->and($activation->isActive())->toBeFalse();
});
