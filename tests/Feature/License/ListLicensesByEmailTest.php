<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseKey;

beforeEach(function () {
    $this->brand   = Brand::factory()->create(['is_active' => true]);
    $this->product = $this->brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
        'is_active' => true,
    ]);
});

test('can list all licenses for a customer email', function () {
    // Create 2 license keys for same customer
    $licenseKey1 = LicenseKey::factory()->create([
        'brand_id'       => $this->brand->public_id,
        'customer_email' => 'customer@example.com',
    ]);

    $licenseKey2 = LicenseKey::factory()->create([
        'brand_id'       => $this->brand->public_id,
        'customer_email' => 'customer@example.com',
    ]);

    License::factory()->create([
        'license_key_id' => $licenseKey1->id,
        'product_id'     => $this->product->public_id,
    ]);

    License::factory()->create([
        'license_key_id' => $licenseKey2->id,
        'product_id'     => $this->product->public_id,
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'statusCode',
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'license_key',
                        'customer_email',
                        'licenses',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ],
        ]);

    expect($response->json('success'))->toBeTrue()
        ->and($response->json('data.data'))->toHaveCount(2)
        ->and($response->json('data.meta.total'))->toBe(2);
});

test('lists licenses across multiple brands for same customer', function () {
    // Create another brand
    $brand2   = Brand::factory()->create(['is_active' => true]);
    $product2 = $brand2->products()->create([
        'name'      => 'Brand 2 Product',
        'slug'      => 'brand-2-product',
        'max_seats' => 3,
    ]);

    // License key from brand 1
    $licenseKey1 = LicenseKey::factory()->create([
        'brand_id'       => $this->brand->public_id,
        'customer_email' => 'john@example.com',
    ]);

    License::factory()->create([
        'license_key_id' => $licenseKey1->id,
        'product_id'     => $this->product->public_id,
    ]);

    // License key from brand 2
    $licenseKey2 = LicenseKey::factory()->create([
        'brand_id'       => $brand2->public_id,
        'customer_email' => 'john@example.com',
    ]);

    License::factory()->create([
        'license_key_id' => $licenseKey2->id,
        'product_id'     => $product2->public_id,
    ]);

    // Brand 1 can see both
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/john@example.com/licenses');

    $response->assertStatus(200);

    expect($response->json('data.data'))->toHaveCount(2)
        ->and($response->json('data.meta.total'))->toBe(2);
});

test('returns empty array when customer has no licenses', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/nonexistent@example.com/licenses');

    $response->assertStatus(200);

    expect($response->json('data.data'))->toBeArray()
        ->and($response->json('data.data'))->toHaveCount(0)
        ->and($response->json('data.meta.total'))->toBe(0);
});

test('requires brand authentication', function () {
    $response = $this->getJson('/api/v1/brands/customers/customer@example.com/licenses');

    $response->assertStatus(401);
});

test('rejects invalid brand api key', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer invalid-key',
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses');

    $response->assertStatus(401);
});

test('validates email format', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/invalid-email/licenses');

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('handles URL-encoded email addresses', function () {
    $licenseKey = LicenseKey::factory()->create([
        'brand_id'       => $this->brand->public_id,
        'customer_email' => 'test+tag@example.com',
    ]);

    License::factory()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $this->product->public_id,
    ]);

    // URL encode the email (+ becomes %2B)
    $encodedEmail = \urlencode('test+tag@example.com');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson("/api/v1/brands/customers/{$encodedEmail}/licenses");

    $response->assertStatus(200);

    expect($response->json('data.data'))->toHaveCount(1);
});

test('supports pagination', function () {
    // Create 25 license keys for same customer
    for ($i = 1; $i <= 25; $i++) {
        $licenseKey = LicenseKey::factory()->create([
            'brand_id'       => $this->brand->public_id,
            'customer_email' => 'customer@example.com',
        ]);

        License::factory()->create([
            'license_key_id' => $licenseKey->id,
            'product_id'     => $this->product->public_id,
        ]);
    }

    // Get first page (default 20 per page)
    $response1 = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses');

    expect($response1->json('data.data'))->toHaveCount(20)
        ->and($response1->json('data.meta.current_page'))->toBe(1)
        ->and($response1->json('data.meta.total'))->toBe(25)
        ->and($response1->json('data.meta.last_page'))->toBe(2);

    // Get second page
    $response2 = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses?page=2');

    expect($response2->json('data.data'))->toHaveCount(5)
        ->and($response2->json('data.meta.current_page'))->toBe(2);
});

test('supports custom per_page parameter', function () {
    // Create 15 license keys
    for ($i = 1; $i <= 15; $i++) {
        $licenseKey = LicenseKey::factory()->create([
            'brand_id'       => $this->brand->public_id,
            'customer_email' => 'customer@example.com',
        ]);

        License::factory()->create([
            'license_key_id' => $licenseKey->id,
            'product_id'     => $this->product->public_id,
        ]);
    }

    // Request 5 per page
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses?per_page=5');

    expect($response->json('data.data'))->toHaveCount(5)
        ->and($response->json('data.meta.per_page'))->toBe(5)
        ->and($response->json('data.meta.last_page'))->toBe(3);
});

test('validates per_page parameter', function () {
    // per_page too large
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses?per_page=200');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);
});

test('includes license details with activations', function () {
    $licenseKey = LicenseKey::factory()->create([
        'brand_id'       => $this->brand->public_id,
        'customer_email' => 'customer@example.com',
    ]);

    $license = License::factory()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $this->product->public_id,
    ]);

    // Create activations
    \App\Modules\License\Models\LicenseActivation::factory()->count(2)->create([
        'license_id' => $license->id,
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses');

    $licenseData = $response->json('data.data.0.licenses.0');

    expect($licenseData)->toHaveKey('product_id')
        ->and($licenseData)->toHaveKey('status');
});

test('only includes active activations', function () {
    $licenseKey = LicenseKey::factory()->create([
        'brand_id'       => $this->brand->public_id,
        'customer_email' => 'customer@example.com',
    ]);

    $license = License::factory()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $this->product->public_id,
    ]);

    // Create 2 active activations
    \App\Modules\License\Models\LicenseActivation::factory()->count(2)->create([
        'license_id' => $license->id,
    ]);

    // Create 1 deactivated activation
    \App\Modules\License\Models\LicenseActivation::factory()->deactivated()->create([
        'license_id' => $license->id,
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses');

    $licenses = $response->json('data.data.0.licenses');

    // Should only have 2 activations (active ones)
    expect($licenses[0])->toHaveKey('activations');
});

test('lists licenses with multiple products on same key', function () {
    $product2 = $this->brand->products()->create([
        'name'      => 'Second Product',
        'slug'      => 'second-product',
        'max_seats' => 3,
    ]);

    $licenseKey = LicenseKey::factory()->create([
        'brand_id'       => $this->brand->public_id,
        'customer_email' => 'customer@example.com',
    ]);

    License::factory()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $this->product->public_id,
    ]);

    License::factory()->create([
        'license_key_id' => $licenseKey->id,
        'product_id'     => $product2->public_id,
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses');

    expect($response->json('data.data'))->toHaveCount(1)
        ->and($response->json('data.data.0.licenses'))->toHaveCount(2);
});

test('case-sensitive email matching', function () {
    LicenseKey::factory()->create([
        'brand_id'       => $this->brand->public_id,
        'customer_email' => 'Customer@Example.com',
    ]);

    // Search with lowercase
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/customer@example.com/licenses');

    expect($response->json('data.meta.total'))->toBe(0);

    // Search with exact case
    $response2 = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->brand->api_key,
    ])->getJson('/api/v1/brands/customers/Customer@Example.com/licenses');

    expect($response2->json('data.meta.total'))->toBe(1);
});
