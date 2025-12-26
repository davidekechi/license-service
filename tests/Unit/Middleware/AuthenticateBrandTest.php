<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\Shared\Middleware\AuthenticateBrand;
use Illuminate\Http\Request;

test('authenticates brand with valid api key', function () {
    $brand = Brand::factory()->create(['is_active' => true]);

    $request = Request::create('/api/test', 'GET');
    $request->headers->set('Authorization', 'Bearer ' . $brand->api_key);

    $middleware = app(AuthenticateBrand::class);

    $response = $middleware->handle($request, function ($req) {
        expect($req->get('authenticated_brand'))->not->toBeNull();

        return response()->json(['success' => true]);
    });

    expect($response->getStatusCode())->toBe(200);
});

test('rejects request without api key', function () {
    $request = Request::create('/api/test', 'GET');

    $middleware = app(AuthenticateBrand::class);

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['success' => true]);
    });

    expect($response->getStatusCode())->toBe(401)
        ->and(\json_decode($response->getContent(), true))->toHaveKey('message', 'API key is required');
});

test('rejects request with invalid api key', function () {
    $request = Request::create('/api/test', 'GET');
    $request->headers->set('Authorization', 'Bearer invalid-key');

    $middleware = app(AuthenticateBrand::class);

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['success' => true]);
    });

    expect($response->getStatusCode())->toBe(401)
        ->and(\json_decode($response->getContent(), true))->toHaveKey('message', 'Invalid API key');
});

test('rejects request with inactive brand', function () {
    $brand = Brand::factory()->inactive()->create();

    $request = Request::create('/api/test', 'GET');
    $request->headers->set('Authorization', 'Bearer ' . $brand->api_key);

    $middleware = app(AuthenticateBrand::class);

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['success' => true]);
    });

    expect($response->getStatusCode())->toBe(401)
        ->and(\json_decode($response->getContent(), true))->toHaveKey('message', 'Invalid API key');

    // expect($response->getStatusCode())->toBe(403)
    //     ->and(\json_decode($response->getContent(), true))->toHaveKey('message', 'Brand account is inactive');
});

test('supports api key without bearer prefix', function () {
    $brand = Brand::factory()->create(['is_active' => true]);

    $request = Request::create('/api/test', 'GET');
    $request->headers->set('Authorization', $brand->api_key);

    $middleware = app(AuthenticateBrand::class);

    $response = $middleware->handle($request, function ($req) {
        expect($req->get('authenticated_brand'))->not->toBeNull();

        return response()->json(['success' => true]);
    });

    expect($response->getStatusCode())->toBe(200);
});
