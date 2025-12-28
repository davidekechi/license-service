<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;

test('returns consistent JSON error for validation errors', function () {
    $brand = Brand::factory()->create(['is_active' => true]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        // Missing required fields
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'statusCode',
            'success',
            'message',
            'errors',
        ])
        ->assertJsonPath('success', false)
        ->assertJsonPath('statusCode', 422);
});

test('returns consistent JSON error for authentication errors', function () {
    $response = $this->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'test@example.com',
        'products'       => [],
    ]);

    $response->assertStatus(401)
        ->assertJsonStructure([
            'statusCode',
            'success',
            'message',
        ])
        ->assertJsonPath('success', false)
        ->assertJsonPath('statusCode', 401);
});

test('returns consistent JSON error for not found errors', function () {
    $response = $this->getJson('/api/v1/licenses/FAKE-1234-5678-9ABC/status');

    $response->assertStatus(404)
        ->assertJsonStructure([
            'statusCode',
            'success',
            'message',
        ])
        ->assertJsonPath('success', false)
        ->assertJsonPath('statusCode', 404);
});

test('returns consistent JSON error for rate limit', function () {
    // Make requests until rate limited
    for ($i = 0; $i < 125; $i++) {
        $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');
    }

    $response->assertStatus(429)
        ->assertJsonStructure([
            'statusCode',
            'success',
            'message',
        ])
        ->assertJsonPath('success', false)
        ->assertJsonPath('statusCode', 429);
});

test('includes API version header in responses', function () {
    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $response->assertHeader('X-API-Version', 'v1')
        ->assertHeader('X-Application-Name', 'License Service');
});

test('handles endpoint not found with JSON response', function () {
    $response = $this->getJson('/api/v1/nonexistent-endpoint');

    $response->assertStatus(404)
        ->assertJsonStructure([
            'statusCode',
            'success',
            'message',
        ])
        ->assertJsonPath('message', 'Endpoint not found');
});
