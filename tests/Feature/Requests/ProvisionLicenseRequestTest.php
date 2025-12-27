<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Requests\ProvisionLicenseRequest;
use Illuminate\Support\Facades\Validator;

test('provision license request validates successfully with valid data', function () {
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    $data = [
        'customer_email' => 'test@example.com',
        'products'       => [
            [
                'product_public_id' => $product->public_id,
                'expires_at'        => now()->addYear()->toDateString(),
                'max_activations'   => 5,
            ],
        ],
    ];

    $request   = new ProvisionLicenseRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('provision license request requires customer email', function () {
    $data = [
        'products' => [
            ['product_public_id' => 'test-product'],
        ],
    ];

    $request   = new ProvisionLicenseRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('customer_email'))->toBeTrue();
});

test('provision license request validates email format', function () {
    $data = [
        'customer_email' => 'invalid-email',
        'products'       => [
            ['product_public_id' => 'test-product'],
        ],
    ];

    $request   = new ProvisionLicenseRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('customer_email'))->toBeTrue();
});

test('provision license request requires at least one product', function () {
    $data = [
        'customer_email' => 'test@example.com',
        'products'       => [],
    ];

    $request   = new ProvisionLicenseRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('products'))->toBeTrue();
});

test('provision license request validates expiration date is in future', function () {
    $data = [
        'customer_email' => 'test@example.com',
        'products'       => [
            [
                'product_public_id' => 'test-product',
                'expires_at'        => now()->subDay()->toDateString(),
            ],
        ],
    ];

    $request   = new ProvisionLicenseRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('products.0.expires_at'))->toBeTrue();
});
