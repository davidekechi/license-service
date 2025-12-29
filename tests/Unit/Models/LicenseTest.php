<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Enums\LicenseStatus;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseActivation;
use App\Modules\License\Models\LicenseKey;

test('license can be created', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    $licenseKey = LicenseKey::create([
        'key'            => 'TEST-1234-5678-9ABC',
        'brand_id'       => $brand->public_id,
        'customer_email' => 'test@example.com',
    ]);

    $license = License::create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'status'          => LicenseStatus::VALID,
        'expires_at'      => now()->addYear(),
        'max_activations' => 5,
    ]);

    expect($license)->toBeModel(License::class)
        ->and($license->status)->toBe(LicenseStatus::VALID)
        ->and($license->max_activations)->toBe(5)
        ->and($license)->toHaveUlid();
});

test('license isValid method works correctly', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);

    $validLicense = License::create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'status'          => LicenseStatus::VALID,
        'expires_at'      => now()->addYear(),
        'max_activations' => 5,
    ]);

    expect($validLicense->isValid())->toBeTrue();
});

test('license isExpired method works correctly', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);

    $expiredLicense = License::create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'status'          => LicenseStatus::VALID,
        'expires_at'      => now()->subDay(),
        'max_activations' => 5,
    ]);

    $validLicense = License::create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'status'          => LicenseStatus::VALID,
        'expires_at'      => now()->addYear(),
        'max_activations' => 5,
    ]);

    expect($expiredLicense->isExpired())->toBeTrue()
        ->and($validLicense->isExpired())->toBeFalse();
});

test('license hasUnlimitedActivations works correctly', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);

    $limitedLicense = License::create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'status'          => LicenseStatus::VALID,
        'max_activations' => 5,
    ]);

    $unlimitedLicense = License::create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'status'          => LicenseStatus::VALID,
        'max_activations' => -1,
    ]);

    expect($limitedLicense->hasUnlimitedActivations())->toBeFalse()
        ->and($unlimitedLicense->hasUnlimitedActivations())->toBeTrue();
});

test('license has many activations', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);

    $license = License::create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'status'          => LicenseStatus::VALID,
        'max_activations' => 5,
    ]);

    LicenseActivation::create([
        'license_id'          => $license->id,
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'activated_at'        => now(),
    ]);

    expect($license->activations)->toHaveCount(1);
});
