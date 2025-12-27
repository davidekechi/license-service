<?php

declare(strict_types=1);

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Brand\Models\Brand;

beforeEach(function () {
    $this->brand   = Brand::factory()->create(['is_active' => true]);
    $this->product = $this->brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
        'is_active' => true,
    ]);
});

test('can provision license with single product', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $this->product->public_id,
                'expires_at'        => now()->addYear()->toDateString(),
                'max_activations'   => 5,
            ],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'statusCode',
            'success',
            'message',
            'data' => [
                'license_key',
                'customer_email',
                'licenses',
            ],
        ]);

    expect($response->json('success'))->toBeTrue()
        ->and($response->json('data.customer_email'))->toBe('customer@example.com')
        ->and($response->json('data.licenses'))->toHaveCount(1);
});

test('can provision license with multiple products', function () {
    $product2 = $this->brand->products()->create([
        'name'      => 'Second Product',
        'slug'      => 'second-product',
        'max_seats' => 3,
        'is_active' => true,
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $this->product->public_id,
                'expires_at'        => now()->addYear()->toDateString(),
                'max_activations'   => 5,
            ],
            [
                'product_public_id' => $product2->public_id,
                'expires_at'        => now()->addYear()->toDateString(),
                'max_activations'   => 3,
            ],
        ],
    ]);

    $response->assertStatus(201);

    expect($response->json('data.licenses'))->toHaveCount(2);
});

test('can add product to existing license key', function () {
    // First provision
    $firstResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $this->product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $licenseKey = $firstResponse->json('data.license_key');

    // Create second product
    $product2 = $this->brand->products()->create([
        'name'      => 'Second Product',
        'slug'      => 'second-product',
        'max_seats' => 3,
    ]);

    // Add to existing key
    $secondResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'license_key'    => $licenseKey,
        'products'       => [
            [
                'product_public_id' => $product2->public_id,
                'max_activations'   => 3,
            ],
        ],
    ]);

    $secondResponse->assertStatus(201);

    expect($secondResponse->json('data.license_key'))->toBe($licenseKey)
        ->and($secondResponse->json('data.licenses'))->toHaveCount(2);
});

test('rejects provision without authentication', function () {
    $response = $this->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $this->product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $response->assertStatus(401);
});

test('rejects provision with invalid brand api key', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer invalid-key',
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $this->product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $response->assertStatus(401);
});

test('rejects provision with product from different brand', function () {
    $otherBrand   = Brand::factory()->create();
    $otherProduct = $otherBrand->products()->create([
        'name'      => 'Other Brand Product',
        'slug'      => 'other-product',
        'max_seats' => 5,
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $otherProduct->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'One or more products do not belong to this brand',
        ]);
});

test('rejects duplicate product on same license key', function () {
    // First provision
    $firstResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $this->product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $licenseKey = $firstResponse->json('data.license_key');

    // Try to add same product again
    $secondResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'license_key'    => $licenseKey,
        'products'       => [
            [
                'product_public_id' => $this->product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $secondResponse->assertStatus(400)
        ->assertJsonPath('message', 'License already exists for product: ' . $this->product->public_id);
});

test('validates required fields', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        // Missing customer_email and products
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['customer_email', 'products']);
});

test('validates email format', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'invalid-email',
        'products'       => [
            [
                'product_public_id' => $this->product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['customer_email']);
});

test('validates products array is not empty', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['products']);
});

test('creates audit log on successful provision', function () {
    $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $this->product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    // Dispatch queued events
    $this->artisan('queue:work --once');

    $auditLog = AuditLog::where('event', 'license_provisioned')->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->actor_type)->toBe('brand')
        ->and($auditLog->actor_identifier)->toBe($this->brand->public_id);
});

test('provision creates license key with correct format', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $this->product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $licenseKey = $response->json('data.license_key');

    expect($licenseKey)->toMatch('/^[A-Z]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/');
});
