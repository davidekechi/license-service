<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Models\LicenseKey;
use App\Modules\License\Repositories\LicenseKeyRepository;

beforeEach(function () {
    $this->repository = new LicenseKeyRepository(new LicenseKey());
});

test('can create a license key', function () {
    $brand = Brand::factory()->create();

    $data = [
        'key'            => 'TEST-1234-5678-9ABC',
        'brand_id'       => $brand->public_id,
        'customer_email' => 'test@example.com',
    ];

    $licenseKey = $this->repository->create($data);

    expect($licenseKey)->toBeModel(LicenseKey::class)
        ->and($licenseKey->key)->toBe('TEST-1234-5678-9ABC')
        ->and($licenseKey->customer_email)->toBe('test@example.com');
});

test('can find license key by id', function () {
    $licenseKey = LicenseKey::factory()->create();

    $found = $this->repository->findById($licenseKey->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($licenseKey->id);
});

test('can find license key by public id', function () {
    $licenseKey = LicenseKey::factory()->create();

    $found = $this->repository->findByPublicId($licenseKey->public_id);

    expect($found)->not->toBeNull()
        ->and($found->public_id)->toBe($licenseKey->public_id);
});

test('can find license key by key string', function () {
    $licenseKey = LicenseKey::factory()->create(['key' => 'TEST-ABCD-EFGH-IJKL']);

    $found = $this->repository->findByKey('TEST-ABCD-EFGH-IJKL');

    expect($found)->not->toBeNull()
        ->and($found->key)->toBe('TEST-ABCD-EFGH-IJKL');
});

test('can get license keys by customer email', function () {
    LicenseKey::factory()->count(3)->create(['customer_email' => 'same@example.com']);
    LicenseKey::factory()->count(2)->create(['customer_email' => 'different@example.com']);

    $licenseKeys = $this->repository->getByCustomerEmail('same@example.com');

    expect($licenseKeys)->toHaveCount(3);
});

test('can get license keys by brand id', function () {
    $brand = Brand::factory()->create();
    LicenseKey::factory()->count(3)->create(['brand_id' => $brand->public_id]);
    LicenseKey::factory()->count(2)->create(); // Different brand

    $licenseKeys = $this->repository->getByBrandId($brand->public_id);

    expect($licenseKeys)->toHaveCount(3);
});

test('can update a license key', function () {
    $licenseKey = LicenseKey::factory()->create(['customer_email' => 'old@example.com']);

    $updated = $this->repository->update($licenseKey, ['customer_email' => 'new@example.com']);

    expect($updated->customer_email)->toBe('new@example.com')
        ->and($updated->id)->toBe($licenseKey->id);
});

test('can soft delete a license key', function () {
    $licenseKey = LicenseKey::factory()->create();

    $result = $this->repository->delete($licenseKey);

    expect($result)->toBeTrue()
        ->and(LicenseKey::find($licenseKey->id))->toBeNull()
        ->and(LicenseKey::withTrashed()->find($licenseKey->id))->not->toBeNull();
});
