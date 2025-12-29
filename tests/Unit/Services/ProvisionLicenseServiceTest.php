<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Models\LicenseKey;
use App\Modules\License\Services\ProvisionLicenseService;

beforeEach(function () {
    $this->service = app(ProvisionLicenseService::class);
});

test('can provision new license with generated key', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    $licenseKey = $this->service->provision(
        brandPublicId: $brand->public_id,
        customerEmail: 'customer@example.com',
        products: [
            [
                'product_public_id' => $product->public_id,
                'expires_at'        => now()->addYear()->toDateTimeString(),
                'max_activations'   => 5,
            ],
        ]
    );

    expect($licenseKey)->toBeModel(LicenseKey::class)
        ->and($licenseKey->customer_email)->toBe('customer@example.com')
        ->and($licenseKey->licenses)->toHaveCount(1);
});

test('can add license to existing key', function () {
    $brand    = Brand::factory()->create();
    $product1 = $brand->products()->create([
        'name'      => 'Product 1',
        'slug'      => 'product-1',
        'max_seats' => 5,
    ]);
    $product2 = $brand->products()->create([
        'name'      => 'Product 2',
        'slug'      => 'product-2',
        'max_seats' => 5,
    ]);

    // First provision
    $licenseKey = $this->service->provision(
        brandPublicId: $brand->public_id,
        customerEmail: 'customer@example.com',
        products: [
            [
                'product_public_id' => $product1->public_id,
                'expires_at'        => now()->addYear()->toDateTimeString(),
                'max_activations'   => 5,
            ],
        ]
    );

    // Add to existing key
    $updatedLicenseKey = $this->service->provision(
        brandPublicId: $brand->public_id,
        customerEmail: 'customer@example.com',
        products: [
            [
                'product_public_id' => $product2->public_id,
                'expires_at'        => now()->addYear()->toDateTimeString(),
                'max_activations'   => 5,
            ],
        ],
        existingLicenseKey: $licenseKey->key
    );

    expect($updatedLicenseKey->key)->toBe($licenseKey->key)
        ->and($updatedLicenseKey->licenses)->toHaveCount(2);
});

test('throws exception when product does not belong to brand', function () {
    $brand1  = Brand::factory()->create();
    $brand2  = Brand::factory()->create();
    $product = $brand2->products()->create([
        'name'      => 'Other Brand Product',
        'slug'      => 'other-product',
        'max_seats' => 5,
    ]);

    $this->service->provision(
        brandPublicId: $brand1->public_id,
        customerEmail: 'customer@example.com',
        products: [
            [
                'product_public_id' => $product->public_id,
                'expires_at'        => now()->addYear()->toDateTimeString(),
                'max_activations'   => 5,
            ],
        ]
    );
})->throws(InvalidArgumentException::class, 'One or more products do not belong to this brand');

test('throws exception when license key belongs to different brand', function () {
    $brand1   = Brand::factory()->create();
    $brand2   = Brand::factory()->create();
    $product1 = $brand1->products()->create([
        'name'      => 'Product 1',
        'slug'      => 'product-1',
        'max_seats' => 5,
    ]);

    $existingKey = LicenseKey::factory()->create([
        'brand_id'       => $brand2->public_id,
        'customer_email' => 'customer@example.com',
    ]);

    $this->service->provision(
        brandPublicId: $brand1->public_id,
        customerEmail: 'customer@example.com',
        products: [
            [
                'product_public_id' => $product1->public_id,
                'expires_at'        => now()->addYear()->toDateTimeString(),
                'max_activations'   => 5,
            ],
        ],
        existingLicenseKey: $existingKey->key
    );
})->throws(InvalidArgumentException::class, 'License key belongs to different brand');

test('throws exception when duplicate license for product', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    $licenseKey = $this->service->provision(
        brandPublicId: $brand->public_id,
        customerEmail: 'customer@example.com',
        products: [
            [
                'product_public_id' => $product->public_id,
                'expires_at'        => now()->addYear()->toDateTimeString(),
                'max_activations'   => 5,
            ],
        ]
    );

    // Try to add same product again
    $this->service->provision(
        brandPublicId: $brand->public_id,
        customerEmail: 'customer@example.com',
        products: [
            [
                'product_public_id' => $product->public_id,
                'expires_at'        => now()->addYear()->toDateTimeString(),
                'max_activations'   => 5,
            ],
        ],
        existingLicenseKey: $licenseKey->key
    );
})->throws(InvalidArgumentException::class, 'License already exists for product');
