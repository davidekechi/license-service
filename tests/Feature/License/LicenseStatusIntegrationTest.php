<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Models\LicenseActivation;

test('complete workflow: provision -> activate -> check status', function () {
    // Setup
    $brand   = Brand::factory()->create(['is_active' => true]);
    $product = $brand->products()->create([
        'name'      => 'WP Rocket',
        'slug'      => 'wp-rocket',
        'max_seats' => 3,
    ]);

    // Step 1: Provision license
    $provisionResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $product->public_id,
                'expires_at'        => now()->addYear()->toDateString(),
                'max_activations'   => 3,
            ],
        ],
    ]);

    $licenseKey = $provisionResponse->json('data.license_key');

    // Step 2: Activate on 2 sites
    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site1.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ])->assertStatus(201);

    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site2.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ])->assertStatus(201);

    // Step 3: Check status
    $statusResponse = $this->getJson("/api/v1/licenses/{$licenseKey}/status");

    $statusResponse->assertStatus(200);

    $licenseData = $statusResponse->json('data.licenses.0');

    expect($licenseData['seats']['used'])->toBe(2)
        ->and($licenseData['seats']['available'])->toBe(1)
        ->and($licenseData['seats']['total'])->toBe(3)
        ->and($licenseData['activations'])->toHaveCount(2);
});

test('status reflects real-time seat availability after activations', function () {
    // Setup
    $brand   = Brand::factory()->create(['is_active' => true]);
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    // Provision
    $provisionResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $licenseKey = $provisionResponse->json('data.license_key');

    // Check initial status (0 seats used)
    $status1 = $this->getJson("/api/v1/licenses/{$licenseKey}/status");
    expect($status1->json('data.licenses.0.seats.used'))->toBe(0);

    // Activate 1 instance
    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site1.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ]);

    // Check status (1 seat used)
    $status2 = $this->getJson("/api/v1/licenses/{$licenseKey}/status");
    expect($status2->json('data.licenses.0.seats.used'))->toBe(1)
        ->and($status2->json('data.licenses.0.seats.available'))->toBe(4);

    // Activate 4 more instances
    for ($i = 2; $i <= 5; $i++) {
        $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
            'instance_identifier' => "https://site{$i}.com",
            'instance_type'       => 'site',
            'product_public_id'   => $product->public_id,
        ]);
    }

    // Check final status (5 seats used)
    $status3 = $this->getJson("/api/v1/licenses/{$licenseKey}/status");
    expect($status3->json('data.licenses.0.seats.used'))->toBe(5)
        ->and($status3->json('data.licenses.0.seats.available'))->toBe(0);
});

test('status shows different states for different products on same key', function () {
    // Setup
    $brand = Brand::factory()->create(['is_active' => true]);

    $product1 = $brand->products()->create([
        'name'      => 'Active Product',
        'slug'      => 'active-product',
        'max_seats' => 5,
    ]);

    $product2 = $brand->products()->create([
        'name'      => 'Full Product',
        'slug'      => 'full-product',
        'max_seats' => 2,
    ]);

    // Provision with both products
    $provisionResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $product1->public_id,
                'max_activations'   => 5,
            ],
            [
                'product_public_id' => $product2->public_id,
                'max_activations'   => 2,
            ],
        ],
    ]);

    $licenseKey = $provisionResponse->json('data.license_key');

    // Activate Product 1: 2/5 seats
    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site1.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product1->public_id,
    ]);

    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site2.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product1->public_id,
    ]);

    // Activate Product 2: 2/2 seats (full)
    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site1.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product2->public_id,
    ]);

    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site2.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product2->public_id,
    ]);

    // Check status
    $statusResponse = $this->getJson("/api/v1/licenses/{$licenseKey}/status");

    /** @var array<int, array<string, mixed>> $licenses */
    $licenses = $statusResponse->json('data.licenses');

    // Find each license by checking seats
    $license1 = collect($licenses)->firstWhere('seats.total', 5);
    $license2 = collect($licenses)->firstWhere('seats.total', 2);

    expect($license1['seats']['used'])->toBe(2)
        ->and($license1['seats']['available'])->toBe(3)
        ->and($license2['seats']['used'])->toBe(2)
        ->and($license2['seats']['available'])->toBe(0);
});

test('heartbeat tracking over multiple status checks', function () {
    // Setup
    $brand   = Brand::factory()->create(['is_active' => true]);
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    // Provision and activate
    $provisionResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $product->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $licenseKey = $provisionResponse->json('data.license_key');

    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ]);

    $activation     = LicenseActivation::first();
    $firstHeartbeat = $activation->last_checked_at;

    // Wait and check status again
    \sleep(1);
    $this->getJson("/api/v1/licenses/{$licenseKey}/status");

    $activation->refresh();
    $secondHeartbeat = $activation->last_checked_at;

    // Wait and check status again
    \sleep(1);
    $this->getJson("/api/v1/licenses/{$licenseKey}/status");

    $activation->refresh();
    $thirdHeartbeat = $activation->last_checked_at;

    expect($secondHeartbeat)->toBeGreaterThan($firstHeartbeat)
        ->and($thirdHeartbeat)->toBeGreaterThan($secondHeartbeat);
});

test('customer can check their license status across multiple products', function () {
    // Setup
    $brand = Brand::factory()->create(['is_active' => true]);

    $rankMathPro = $brand->products()->create([
        'name'      => 'RankMath Pro',
        'slug'      => 'rankmath-pro',
        'max_seats' => 5,
    ]);

    $contentAI = $brand->products()->create([
        'name'      => 'Content AI',
        'slug'      => 'content-ai',
        'max_seats' => 5,
    ]);

    // Customer purchases RankMath Pro
    $step1 = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'john@example.com',
        'products'       => [
            [
                'product_public_id' => $rankMathPro->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $licenseKey = $step1->json('data.license_key');

    // Customer adds Content AI
    $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'john@example.com',
        'license_key'    => $licenseKey,
        'products'       => [
            [
                'product_public_id' => $contentAI->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    // Customer checks status - should see both products
    $statusResponse = $this->getJson("/api/v1/licenses/{$licenseKey}/status");

    expect($statusResponse->json('data.licenses'))->toHaveCount(2)
        ->and($statusResponse->json('data.customer_email'))->toBe('john@example.com');
});
