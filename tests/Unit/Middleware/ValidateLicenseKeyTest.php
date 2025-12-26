<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Models\LicenseKey;
use App\Modules\Shared\Middleware\ValidateLicenseKey;
use Illuminate\Http\Request;

test('validates existing license key', function () {
    $brand      = Brand::factory()->create();
    $licenseKey = LicenseKey::factory()->create([
        'brand_id' => $brand->public_id,
        'key'      => 'TEST-1234-5678-9ABC',
    ]);

    $request = Request::create('/api/licenses/TEST-1234-5678-9ABC/status', 'GET');
    $request->setRouteResolver(function () use ($licenseKey) {
        return new class ($licenseKey->key) {
            public function __construct(private string $key)
            {
            }

            public function parameter(string $name): ?string
            {
                return $name === 'licenseKey' ? $this->key : null;
            }
        };
    });

    $middleware = app(ValidateLicenseKey::class);

    $response = $middleware->handle($request, function ($req) {
        expect($req->get('validated_license_key'))->not->toBeNull();

        return response()->json(['success' => true]);
    });

    expect($response->getStatusCode())->toBe(200);
});

test('rejects non-existent license key', function () {
    $request = Request::create('/api/licenses/FAKE-1234-5678-9ABC/status', 'GET');
    $request->setRouteResolver(function () {
        return new class () {
            public function parameter(string $name): ?string
            {
                return $name === 'licenseKey' ? 'FAKE-1234-5678-9ABC' : null;
            }
        };
    });

    $middleware = app(ValidateLicenseKey::class);

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['success' => true]);
    });

    expect($response->getStatusCode())->toBe(404)
        ->and(\json_decode($response->getContent(), true))->toHaveKey('message', 'License key not found');
});

test('rejects request without license key in route', function () {
    $request = Request::create('/api/licenses/status', 'GET');
    $request->setRouteResolver(function () {
        return new class () {
            public function parameter(string $name): null
            {
                return null;
            }
        };
    });

    $middleware = app(ValidateLicenseKey::class);

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['success' => true]);
    });

    expect($response->getStatusCode())->toBe(401)
        ->and(\json_decode($response->getContent(), true))->toHaveKey('message', 'License key is required');
});
