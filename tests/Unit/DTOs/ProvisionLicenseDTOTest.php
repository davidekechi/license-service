<?php

declare(strict_types=1);

use App\Modules\License\DTOs\ProductLicenseDTO;
use App\Modules\License\DTOs\ProvisionLicenseDTO;
use Illuminate\Http\Request;

test('can create provision license DTO from array', function () {
    $data = [
        'customer_email' => 'test@example.com',
        'products'       => [
            [
                'product_slug'    => 'test-product',
                'expires_at'      => '2025-12-31',
                'max_activations' => 5,
            ],
        ],
        'license_key' => 'TEST-1234-5678-9ABC',
    ];

    $dto = ProvisionLicenseDTO::fromArray($data);

    expect($dto->customerEmail)->toBe('test@example.com')
        ->and($dto->licenseKey)->toBe('TEST-1234-5678-9ABC')
        ->and($dto->products)->toHaveCount(1)
        ->and($dto->products[0])->toBeInstanceOf(ProductLicenseDTO::class);
});

test('can create provision license DTO from request', function () {
    $request = Request::create('/api/provision', 'POST', [
        'customer_email' => 'test@example.com',
        'products'       => [
            [
                'product_slug'    => 'test-product',
                'expires_at'      => '2025-12-31',
                'max_activations' => 5,
            ],
        ],
    ]);

    $dto = ProvisionLicenseDTO::fromRequest($request);

    expect($dto->customerEmail)->toBe('test@example.com')
        ->and($dto->products)->toHaveCount(1);
});

test('provision license DTO handles multiple products', function () {
    $data = [
        'customer_email' => 'test@example.com',
        'products'       => [
            ['product_slug' => 'product-1', 'max_activations' => 5],
            ['product_slug' => 'product-2', 'max_activations' => 3],
        ],
    ];

    $dto = ProvisionLicenseDTO::fromArray($data);

    expect($dto->products)->toHaveCount(2)
        ->and($dto->products[0]->productPublicId)->toBe('product-1')
        ->and($dto->products[1]->productPublicId)->toBe('product-2');
});
