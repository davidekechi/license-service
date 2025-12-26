<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Enums\LicenseStatus;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseKey;
use App\Modules\License\Services\LicenseValidationService;

beforeEach(function () {
    $this->service = app(LicenseValidationService::class);
});

test('validates valid license successfully', function () {
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
        'status'         => LicenseStatus::VALID,
        'expires_at'     => now()->addYear(),
    ]);

    $this->service->validateForActivation($license);

    expect(true)->toBeTrue(); // No exception thrown
});

test('throws exception for cancelled license', function () {
    $license = License::factory()->cancelled()->create();

    $this->service->validateForActivation($license);
})->throws(RuntimeException::class, 'License has been cancelled');

test('throws exception for suspended license', function () {
    $license = License::factory()->suspended()->create();

    $this->service->validateForActivation($license);
})->throws(RuntimeException::class, 'License is currently suspended');

test('throws exception for expired license', function () {
    $license = License::factory()->expired()->create();

    $this->service->validateForActivation($license);
})->throws(RuntimeException::class, 'License has expired');

test('isUsable returns true for valid license', function () {
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
        'status'         => LicenseStatus::VALID,
        'expires_at'     => now()->addYear(),
    ]);

    expect($this->service->isUsable($license))->toBeTrue();
});

test('isUsable returns false for invalid license', function () {
    $license = License::factory()->cancelled()->create();

    expect($this->service->isUsable($license))->toBeFalse();
});

test('getValidationErrors returns errors for cancelled license', function () {
    $license = License::factory()->cancelled()->create();

    $errors = $this->service->getValidationErrors($license);

    expect($errors)->toContain('License has been cancelled');
});

test('getValidationErrors returns errors for suspended license', function () {
    $license = License::factory()->suspended()->create();

    $errors = $this->service->getValidationErrors($license);

    expect($errors)->toContain('License is currently suspended');
});

test('getValidationErrors returns errors for expired license', function () {
    $license = License::factory()->expired()->create();

    $errors = $this->service->getValidationErrors($license);

    expect($errors)->toContain('License has expired');
});

test('getValidationErrors returns empty array for valid license', function () {
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
        'status'         => LicenseStatus::VALID,
        'expires_at'     => now()->addYear(),
    ]);

    $errors = $this->service->getValidationErrors($license);

    expect($errors)->toBeEmpty();
});
