<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Enums\InstanceType;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseActivation;
use App\Modules\License\Models\LicenseKey;
use App\Modules\License\Repositories\LicenseActivationRepository;

beforeEach(function () {
    $this->repository = new LicenseActivationRepository(new LicenseActivation());
});

test('can create a license activation', function () {
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

    $data = [
        'license_id'          => $license->id,
        'instance_identifier' => 'https://example.com',
        'instance_type'       => InstanceType::SITE,
        'activated_at'        => now(),
    ];

    $activation = $this->repository->create($data);

    expect($activation)->toBeModel(LicenseActivation::class)
        ->and($activation->instance_identifier)->toBe('https://example.com')
        ->and($activation->instance_type)->toBe(InstanceType::SITE);
});

test('can find activation by id', function () {
    $activation = LicenseActivation::factory()->create();

    $found = $this->repository->findById($activation->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($activation->id);
});

test('can find activation by public id', function () {
    $activation = LicenseActivation::factory()->create();

    $found = $this->repository->findByPublicId($activation->public_id);

    expect($found)->not->toBeNull()
        ->and($found->public_id)->toBe($activation->public_id);
});

test('can find activation by license and instance', function () {
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

    $activation = LicenseActivation::factory()->create([
        'license_id'          => $license->id,
        'instance_identifier' => 'https://unique.com',
    ]);

    $found = $this->repository->findByLicenseAndInstance($license->id, 'https://unique.com');

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($activation->id);
});

test('can get activations by license id', function () {
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

    LicenseActivation::factory()->count(3)->create(['license_id' => $license->id]);
    LicenseActivation::factory()->count(2)->create(); // Different license

    $activations = $this->repository->getByLicenseId($license->id);

    expect($activations)->toHaveCount(3);
});

test('can get active activations by license id', function () {
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

    LicenseActivation::factory()->count(3)->create(['license_id' => $license->id]);
    LicenseActivation::factory()->count(2)->deactivated()->create(['license_id' => $license->id]);

    $activeActivations = $this->repository->getActiveByLicenseId($license->id);

    expect($activeActivations)->toHaveCount(3);
});

test('can count active activations', function () {
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

    LicenseActivation::factory()->count(3)->create(['license_id' => $license->id]);
    LicenseActivation::factory()->count(2)->deactivated()->create(['license_id' => $license->id]);

    $count = $this->repository->countActiveByLicenseId($license->id);

    expect($count)->toBe(3);
});

test('can update an activation', function () {
    $activation = LicenseActivation::factory()->create();

    $updated = $this->repository->update($activation, ['last_checked_at' => now()]);

    expect($updated->last_checked_at)->not->toBeNull()
        ->and($updated->id)->toBe($activation->id);
});

test('can soft delete an activation', function () {
    $activation = LicenseActivation::factory()->create();

    $result = $this->repository->delete($activation);

    expect($result)->toBeTrue()
        ->and(LicenseActivation::find($activation->id))->toBeNull()
        ->and(LicenseActivation::withTrashed()->find($activation->id))->not->toBeNull();
});
