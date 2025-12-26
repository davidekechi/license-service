<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Enums\LicenseStatus;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseKey;
use App\Modules\License\Repositories\LicenseRepository;

beforeEach(function () {
    $this->repository = new LicenseRepository(new License());
});

test('can create a license', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);

    $data = [
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'status'          => LicenseStatus::VALID,
        'expires_at'      => now()->addYear(),
        'max_activations' => 5,
    ];

    $license = $this->repository->create($data);

    expect($license)->toBeModel(License::class)
        ->and($license->status)->toBe(LicenseStatus::VALID)
        ->and($license->max_activations)->toBe(5);
});

test('can find license by id', function () {
    $license = License::factory()->create();

    $found = $this->repository->findById($license->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($license->id);
});

test('can find license by public id', function () {
    $license = License::factory()->create();

    $found = $this->repository->findByPublicId($license->public_id);

    expect($found)->not->toBeNull()
        ->and($found->public_id)->toBe($license->public_id);
});

test('can get licenses by license key id', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);

    License::factory()->count(3)->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $product->public_id,
    ]);
    License::factory()->count(2)->create(); // Different license key

    $licenses = $this->repository->getByLicenseKeyId($licenseKey->id);

    expect($licenses)->toHaveCount(3);
});

test('can find license by license key and product', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);

    $license = License::factory()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $product->public_id,
    ]);

    $found = $this->repository->findByLicenseKeyAndProduct($licenseKey->id, $product->public_id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($license->id);
});

test('can update a license', function () {
    $license = License::factory()->create(['status' => LicenseStatus::VALID]);

    $updated = $this->repository->update($license, ['status' => LicenseStatus::SUSPENDED]);

    expect($updated->status)->toBe(LicenseStatus::SUSPENDED)
        ->and($updated->id)->toBe($license->id);
});

test('can soft delete a license', function () {
    $license = License::factory()->create();

    $result = $this->repository->delete($license);

    expect($result)->toBeTrue()
        ->and(License::find($license->id))->toBeNull()
        ->and(License::withTrashed()->find($license->id))->not->toBeNull();
});
