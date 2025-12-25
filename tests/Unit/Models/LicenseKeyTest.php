<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseKey;

test('license key can be created', function () {
    $brand = Brand::create([
        'name'    => 'Test Brand',
        'slug'    => 'test-brand',
        'api_key' => 'test-key',
    ]);

    $licenseKey = LicenseKey::create([
        'key'            => 'TEST-1234-5678-9ABC',
        'brand_id'       => $brand->public_id,
        'customer_email' => 'test@example.com',
    ]);

    expect($licenseKey)->toBeModel(LicenseKey::class)
        ->and($licenseKey->key)->toBe('TEST-1234-5678-9ABC')
        ->and($licenseKey->customer_email)->toBe('test@example.com')
        ->and($licenseKey)->toHaveUlid();
});

test('license key has many licenses', function () {
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
        'status'          => 'valid',
        'max_activations' => 5,
    ]);

    expect($licenseKey->licenses)->toHaveCount(1)
        ->and($licenseKey->licenses->first()->id)->toBe($license->id);
});

test('license key can filter active licenses', function () {
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

    License::create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'status'          => 'valid',
        'max_activations' => 5,
    ]);

    License::create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'status'          => 'suspended',
        'max_activations' => 5,
    ]);

    expect($licenseKey->activeLicenses())->toHaveCount(1);
});
