<?php

declare(strict_types=1);

use App\Modules\License\DTOs\ActivateLicenseDTO;
use App\Modules\License\Enums\InstanceType;
use Illuminate\Http\Request;

test('can create activate license DTO from array', function () {
    $data = [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => 'test-product',
        'instance_meta'       => ['key' => 'value'],
    ];

    $dto = ActivateLicenseDTO::fromArray($data);

    expect($dto->instanceIdentifier)->toBe('https://example.com')
        ->and($dto->instanceType)->toBe(InstanceType::SITE)
        ->and($dto->productPublicId)->toBe('test-product')
        ->and($dto->instanceMeta)->toBe(['key' => 'value']);
});

test('can create activate license DTO from request', function () {
    $request = Request::create('/api/activate', 'POST', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => 'test-product',
    ]);

    $dto = ActivateLicenseDTO::fromRequest($request);

    expect($dto->instanceIdentifier)->toBe('https://example.com')
        ->and($dto->instanceType)->toBe(InstanceType::SITE)
        ->and($dto->productPublicId)->toBe('test-product');
});

test('activate license DTO defaults to site instance type', function () {
    $data = [
        'instance_identifier' => 'https://example.com',
        'product_public_id'   => 'test-product',
    ];

    $dto = ActivateLicenseDTO::fromArray($data);

    expect($dto->instanceType)->toBe(InstanceType::SITE);
});

test('activate license DTO converts to array correctly', function () {
    $dto = new ActivateLicenseDTO(
        instanceIdentifier: 'https://example.com',
        instanceType: InstanceType::DEVICE,
        productPublicId: 'test-product',
        instanceMeta: ['device_id' => '123']
    );

    $array = $dto->toArray();

    expect($array)->toHaveKeys(['instance_identifier', 'instance_type', 'product_public_id', 'instance_meta'])
        ->and($array['instance_type'])->toBe('device');
});
