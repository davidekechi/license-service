<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Repositories\BrandRepository;

beforeEach(function () {
    $this->repository = new BrandRepository(new Brand());
});

test('can create a brand', function () {
    $data = [
        'name'      => 'Test Brand',
        'slug'      => 'test-brand',
        'api_key'   => 'test-api-key',
        'is_active' => true,
    ];

    $brand = $this->repository->create($data);

    expect($brand)->toBeModel(Brand::class)
        ->and($brand->name)->toBe('Test Brand')
        ->and($brand->slug)->toBe('test-brand');
});

test('can find brand by id', function () {
    $brand = Brand::factory()->create();

    $found = $this->repository->findById($brand->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($brand->id);
});

test('can find brand by public id', function () {
    $brand = Brand::factory()->create();

    $found = $this->repository->findByPublicId($brand->public_id);

    expect($found)->not->toBeNull()
        ->and($found->public_id)->toBe($brand->public_id);
});

test('can find brand by slug', function () {
    $brand = Brand::factory()->create(['slug' => 'unique-slug']);

    $found = $this->repository->findBySlug('unique-slug');

    expect($found)->not->toBeNull()
        ->and($found->slug)->toBe('unique-slug');
});

test('can find brand by api key', function () {
    $brand = Brand::factory()->create(['is_active' => true]);

    $found = $this->repository->findByApiKey($brand->api_key);

    expect($found)->not->toBeNull()
        ->and($found->api_key)->toBe($brand->api_key);
});

test('cannot find inactive brand by api key', function () {
    $brand = Brand::factory()->inactive()->create();

    $found = $this->repository->findByApiKey($brand->api_key);

    expect($found)->toBeNull();
});

test('can get all active brands', function () {
    Brand::factory()->count(3)->create(['is_active' => true]);
    Brand::factory()->count(2)->inactive()->create();

    $activeBrands = $this->repository->getAllActive();

    expect($activeBrands)->toHaveCount(3);
});

test('can update a brand', function () {
    $brand = Brand::factory()->create(['name' => 'Original Name']);

    $updated = $this->repository->update($brand, ['name' => 'Updated Name']);

    expect($updated->name)->toBe('Updated Name')
        ->and($updated->id)->toBe($brand->id);
});

test('can soft delete a brand', function () {
    $brand = Brand::factory()->create();

    $result = $this->repository->delete($brand);

    expect($result)->toBeTrue()
        ->and(Brand::find($brand->id))->toBeNull()
        ->and(Brand::withTrashed()->find($brand->id))->not->toBeNull();
});
