<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use Database\Seeders\BrandSeeder;

test('brand seeder creates all brands', function () {
    $seeder = new BrandSeeder();
    $seeder->run(silent: true);

    expect(Brand::count())->toBe(4);
});

test('brand seeder creates rankmath brand', function () {
    $seeder = new BrandSeeder();
    $seeder->run(silent: true);

    $rankmath = Brand::where('slug', 'rankmath')->first();

    expect($rankmath)->not->toBeNull()
        ->and($rankmath->name)->toBe('RankMath')
        ->and($rankmath->is_active)->toBeTrue()
        ->and($rankmath->api_key)->toStartWith('sk_live_rankmath_');
});

test('brand seeder creates wp-rocket brand', function () {
    $seeder = new BrandSeeder();
    $seeder->run(silent: true);

    $wpRocket = Brand::where('slug', 'wp-rocket')->first();

    expect($wpRocket)->not->toBeNull()
        ->and($wpRocket->name)->toBe('WP Rocket')
        ->and($wpRocket->is_active)->toBeTrue();
});

test('brand seeder creates imagify brand', function () {
    $seeder = new BrandSeeder();
    $seeder->run(silent: true);

    $imagify = Brand::where('slug', 'imagify')->first();

    expect($imagify)->not->toBeNull()
        ->and($imagify->name)->toBe('Imagify')
        ->and($imagify->is_active)->toBeTrue();
});

test('brand seeder creates backwpup brand', function () {
    $seeder = new BrandSeeder();
    $seeder->run(silent: true);

    $backwpup = Brand::where('slug', 'backwpup')->first();

    expect($backwpup)->not->toBeNull()
        ->and($backwpup->name)->toBe('BackWPup')
        ->and($backwpup->is_active)->toBeTrue();
});

test('all brands have unique api keys', function () {
    $seeder = new BrandSeeder();
    $seeder->run(silent: true);

    $apiKeys = Brand::pluck('api_key')->toArray();

    expect($apiKeys)->toHaveCount(4)
        ->and(\count(\array_unique($apiKeys)))->toBe(4);
});
