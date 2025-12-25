<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Models\Product;

test('product can be created with valid data', function () {
    $brand = Brand::create([
        'name'    => 'Test Brand',
        'slug'    => 'test-brand',
        'api_key' => 'test-key',
    ]);

    $product = Product::create([
        'brand_id'  => $brand->id,
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
        'is_active' => true,
    ]);

    expect($product)->toBeModel(Product::class)
        ->and($product->name)->toBe('Test Product')
        ->and($product->max_seats)->toBe(5)
        ->and($product)->toHaveUlid();
});

test('product belongs to brand', function () {
    $brand = Brand::create([
        'name'    => 'Test Brand',
        'slug'    => 'test-brand',
        'api_key' => 'test-key',
    ]);

    $product = Product::create([
        'brand_id'  => $brand->id,
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    expect($product->brand)->toBeModel(Brand::class)
        ->and($product->brand->id)->toBe($brand->id);
});

test('product isActive method returns correct value', function () {
    $brand = Brand::factory()->create();

    $activeProduct = Product::create([
        'brand_id'  => $brand->id,
        'name'      => 'Active Product',
        'slug'      => 'active',
        'max_seats' => 5,
        'is_active' => true,
    ]);

    $inactiveProduct = Product::create([
        'brand_id'  => $brand->id,
        'name'      => 'Inactive Product',
        'slug'      => 'inactive',
        'max_seats' => 5,
        'is_active' => false,
    ]);

    expect($activeProduct->isActive())->toBeTrue()
        ->and($inactiveProduct->isActive())->toBeFalse();
});

test('product hasUnlimitedSeats method works correctly', function () {
    $brand = Brand::factory()->create();

    $limitedProduct = Product::create([
        'brand_id'  => $brand->id,
        'name'      => 'Limited',
        'slug'      => 'limited',
        'max_seats' => 5,
    ]);

    $unlimitedProduct = Product::create([
        'brand_id'  => $brand->id,
        'name'      => 'Unlimited',
        'slug'      => 'unlimited',
        'max_seats' => -1,
    ]);

    expect($limitedProduct->hasUnlimitedSeats())->toBeFalse()
        ->and($unlimitedProduct->hasUnlimitedSeats())->toBeTrue();
});
