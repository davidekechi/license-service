<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Models\Product;

test('brand can be created with valid data', function () {
    $brand = Brand::create([
        'name'      => 'Test Brand',
        'slug'      => 'test-brand',
        'api_key'   => 'test-api-key',
        'is_active' => true,
    ]);

    expect($brand)->toBeModel(Brand::class)
        ->and($brand->name)->toBe('Test Brand')
        ->and($brand->slug)->toBe('test-brand')
        ->and($brand)->toHaveUlid();
});

test('brand has ulid generated automatically', function () {
    $brand = Brand::create([
        'name'    => 'Test Brand',
        'slug'    => 'test-brand',
        'api_key' => 'test-api-key',
    ]);

    expect($brand->public_id)->not->toBeNull()
        ->and($brand)->toHaveUlid();
});

test('brand can generate api key', function () {
    $apiKey = Brand::generateApiKey('test-brand');

    expect($apiKey)->toBeString()
        ->and($apiKey)->toStartWith('sk_live_test-brand_')
        ->and(\strlen($apiKey))->toBeGreaterThan(64);
});

test('brand has many products relationship', function () {
    $brand = Brand::create([
        'name'    => 'Test Brand',
        'slug'    => 'test-brand',
        'api_key' => 'test-api-key',
    ]);

    $product = Product::create([
        'brand_id'  => $brand->id,
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    expect($brand->products)->toHaveCount(1)
        ->and($brand->products->first()->id)->toBe($product->id);
});

test('brand isActive method returns correct value', function () {
    $activeBrand = Brand::create([
        'name'      => 'Active Brand',
        'slug'      => 'active-brand',
        'api_key'   => 'test-key',
        'is_active' => true,
    ]);

    $inactiveBrand = Brand::create([
        'name'      => 'Inactive Brand',
        'slug'      => 'inactive-brand',
        'api_key'   => 'test-key-2',
        'is_active' => false,
    ]);

    expect($activeBrand->isActive())->toBeTrue()
        ->and($inactiveBrand->isActive())->toBeFalse();
});

test('brand can be soft deleted', function () {
    $brand = Brand::create([
        'name'    => 'Test Brand',
        'slug'    => 'test-brand',
        'api_key' => 'test-key',
    ]);

    $brand->delete();

    expect($brand->deleted_at)->not->toBeNull()
        ->and(Brand::withTrashed()->find($brand->id))->not->toBeNull()
        ->and(Brand::find($brand->id))->toBeNull();
});
