<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseActivation;
use App\Modules\License\Models\LicenseKey;
use App\Modules\License\Services\SeatManagementService;

beforeEach(function () {
    $this->service = app(SeatManagementService::class);
});

test('counts active seats correctly', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'max_activations' => 5,
    ]);

    LicenseActivation::factory()->count(3)->create(['license_id' => $license->id]);
    LicenseActivation::factory()->count(2)->deactivated()->create(['license_id' => $license->id]);

    $count = $this->service->getActiveSeatCount($license);

    expect($count)->toBe(3);
});

test('calculates available seats correctly', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'max_activations' => 5,
    ]);

    LicenseActivation::factory()->count(2)->create(['license_id' => $license->id]);

    $available = $this->service->getAvailableSeatCount($license);

    expect($available)->toBe(3);
});

test('returns true when seats available', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'max_activations' => 5,
    ]);

    LicenseActivation::factory()->count(3)->create(['license_id' => $license->id]);

    expect($this->service->hasAvailableSeats($license))->toBeTrue();
});

test('returns false when no seats available', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'max_activations' => 5,
    ]);

    LicenseActivation::factory()->count(5)->create(['license_id' => $license->id]);

    expect($this->service->hasAvailableSeats($license))->toBeFalse();
});

test('handles unlimited seats correctly', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => -1,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->unlimited()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $product->public_id,
    ]);

    LicenseActivation::factory()->count(100)->create(['license_id' => $license->id]);

    expect($this->service->hasAvailableSeats($license))->toBeTrue()
        ->and($this->service->getAvailableSeatCount($license))->toBe(PHP_INT_MAX);
});

test('getSeatInfo returns correct structure for limited seats', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'max_activations' => 5,
    ]);

    LicenseActivation::factory()->count(2)->create(['license_id' => $license->id]);

    $info = $this->service->getSeatInfo($license);

    expect($info)->toHaveKeys(['used', 'available', 'total'])
        ->and($info['used'])->toBe(2)
        ->and($info['available'])->toBe(3)
        ->and($info['total'])->toBe(5);
});

test('getSeatInfo returns unlimited for unlimited licenses', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => -1,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->unlimited()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $product->public_id,
    ]);

    LicenseActivation::factory()->count(10)->create(['license_id' => $license->id]);

    $info = $this->service->getSeatInfo($license);

    expect($info['used'])->toBe(10)
        ->and($info['available'])->toBe('unlimited')
        ->and($info['total'])->toBe('unlimited');
});
