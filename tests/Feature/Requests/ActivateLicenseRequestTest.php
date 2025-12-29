<?php

declare(strict_types=1);

use App\Modules\License\Requests\ActivateLicenseRequest;
use Illuminate\Support\Facades\Validator;

test('activate license request validates successfully with valid data', function () {
    $data = [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => 'test-product',
    ];

    $request   = new ActivateLicenseRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('activate license request requires instance identifier', function () {
    $data = [
        'instance_type'     => 'site',
        'product_public_id' => 'test-product',
    ];

    $request   = new ActivateLicenseRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('instance_identifier'))->toBeTrue();
});

test('activate license request validates instance type enum', function () {
    $data = [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'invalid-type',
        'product_public_id'   => 'test-product',
    ];

    $request   = new ActivateLicenseRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('instance_type'))->toBeTrue();
});

test('activate license request accepts all valid instance types', function () {
    $validTypes = ['site', 'device', 'server'];

    foreach ($validTypes as $type) {
        $data = [
            'instance_identifier' => 'https://example.com',
            'instance_type'       => $type,
            'product_public_id'   => 'test-product',
        ];

        $request   = new ActivateLicenseRequest();
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    }
});
