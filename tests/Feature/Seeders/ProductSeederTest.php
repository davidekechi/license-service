<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Models\Product;
use Database\Seeders\BrandSeeder;
use Database\Seeders\ProductSeeder;

beforeEach(function () {
    // Run brand seeder first
    (new BrandSeeder())->run(silent: true);
});

test('product seeder creates all products', function () {
    $seeder = new ProductSeeder();
    $seeder->run(silent: true);

    expect(Product::count())->toBe(9);
});

test('product seeder creates rankmath products', function () {
    $seeder = new ProductSeeder();
    $seeder->run(silent: true);

    $rankmath = Brand::where('slug', 'rankmath')->first();
    $products = Product::where('brand_id', $rankmath->id)->get();

    expect($products)->toHaveCount(3)
        ->and($products->pluck('slug')->toArray())->toContain('rankmath-pro', 'content-ai', 'rankmath-business');
});

test('product seeder creates wp-rocket products', function () {
    $seeder = new ProductSeeder();
    $seeder->run(silent: true);

    $wpRocket = Brand::where('slug', 'wp-rocket')->first();
    $products = Product::where('brand_id', $wpRocket->id)->get();

    expect($products)->toHaveCount(3)
        ->and($products->pluck('slug')->toArray())->toContain('wp-rocket-single', 'wp-rocket-plus', 'wp-rocket-infinite');
});

test('product seeder creates imagify products', function () {
    $seeder = new ProductSeeder();
    $seeder->run(silent: true);

    $imagify  = Brand::where('slug', 'imagify')->first();
    $products = Product::where('brand_id', $imagify->id)->get();

    expect($products)->toHaveCount(2)
        ->and($products->pluck('slug')->toArray())->toContain('imagify', 'imagify-pro');
});

test('product seeder creates backwpup products', function () {
    $seeder = new ProductSeeder();
    $seeder->run(silent: true);

    $backwpup = Brand::where('slug', 'backwpup')->first();
    $products = Product::where('brand_id', $backwpup->id)->get();

    expect($products)->toHaveCount(1)
        ->and($products->first()->slug)->toBe('backwpup-pro');
});

test('product seeder creates products with correct seat limits', function () {
    $seeder = new ProductSeeder();
    $seeder->run(silent: true);

    $unlimited = Product::where('max_seats', -1)->count();
    $limited   = Product::where('max_seats', '>', 0)->count();

    expect($unlimited)->toBeGreaterThan(0)
        ->and($limited)->toBeGreaterThan(0);
});

test('all products are active', function () {
    $seeder = new ProductSeeder();
    $seeder->run(silent: true);

    $inactiveCount = Product::where('is_active', false)->count();

    expect($inactiveCount)->toBe(0);
});
