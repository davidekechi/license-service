<?php

declare(strict_types=1);

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Brand\Models\Brand;
use App\Modules\License\Models\LicenseKey;

test('rankmath scenario: multiple products on single license key', function () {
    // Setup: Create RankMath brand and products
    $rankMath = Brand::factory()->create([
        'name'      => 'RankMath',
        'slug'      => 'rankmath',
        'is_active' => true,
    ]);

    $rankMathPro = $rankMath->products()->create([
        'name'      => 'RankMath Pro',
        'slug'      => 'rankmath-pro',
        'max_seats' => 5,
    ]);

    $contentAI = $rankMath->products()->create([
        'name'      => 'Content AI',
        'slug'      => 'content-ai',
        'max_seats' => 5,
    ]);

    // Step 1: Customer buys RankMath Pro subscription
    $step1Response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rankMath->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'john@example.com',
        'products'       => [
            [
                'product_public_id' => $rankMathPro->public_id,
                'expires_at'        => now()->addYear()->toDateString(),
                'max_activations'   => 5,
            ],
        ],
    ]);

    $step1Response->assertStatus(201);
    $licenseKey1 = $step1Response->json('data.license_key');

    expect($step1Response->json('data.licenses'))->toHaveCount(1);

    // Step 2: Same customer purchases Content AI addon
    $step2Response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rankMath->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'john@example.com',
        'license_key'    => $licenseKey1, // Add to existing key
        'products'       => [
            [
                'product_public_id' => $contentAI->public_id,
                'expires_at'        => now()->addYear()->toDateString(),
                'max_activations'   => 5,
            ],
        ],
    ]);

    $step2Response->assertStatus(201);
    $licenseKey2 = $step2Response->json('data.license_key');

    // Verify same license key
    expect($licenseKey2)->toBe($licenseKey1)
        ->and($step2Response->json('data.licenses'))->toHaveCount(2);

    // Verify database state
    $licenseKeyRecord = LicenseKey::where('key', $licenseKey1)->first();
    expect($licenseKeyRecord->licenses)->toHaveCount(2);
});

test('multi-brand scenario: different brands get different license keys', function () {
    // Setup brands
    $rankMath = Brand::factory()->create([
        'name' => 'RankMath',
        'slug' => 'rankmath',
    ]);

    $wpRocket = Brand::factory()->create([
        'name' => 'WP Rocket',
        'slug' => 'wp-rocket',
    ]);

    $rankMathProduct = $rankMath->products()->create([
        'name'      => 'RankMath Pro',
        'slug'      => 'rankmath-pro',
        'max_seats' => 5,
    ]);

    $wpRocketProduct = $wpRocket->products()->create([
        'name'      => 'WP Rocket',
        'slug'      => 'wp-rocket',
        'max_seats' => 3,
    ]);

    // Customer buys RankMath
    $rankMathResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rankMath->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'john@example.com',
        'products'       => [
            [
                'product_public_id' => $rankMathProduct->public_id,
                'max_activations'   => 5,
            ],
        ],
    ]);

    $licenseKey1 = $rankMathResponse->json('data.license_key');

    // Same customer buys WP Rocket
    $wpRocketResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $wpRocket->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'john@example.com',
        'products'       => [
            [
                'product_public_id' => $wpRocketProduct->public_id,
                'max_activations'   => 3,
            ],
        ],
    ]);

    $licenseKey2 = $wpRocketResponse->json('data.license_key');

    // Verify different license keys
    expect($licenseKey1)->not->toBe($licenseKey2)
        ->and($licenseKey1)->toStartWith('RANK-')
        ->and($licenseKey2)->toStartWith('WPRO-');

    // Verify customer has 2 separate license keys
    expect(LicenseKey::where('customer_email', 'john@example.com')->count())->toBe(2);
});

test('complete provision workflow creates all audit logs', function () {
    $brand   = Brand::factory()->create(['is_active' => true]);
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
    ]);

    // Provision license
    $this->withHeaders([
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

    // Process queue
    $this->artisan('queue:work --once');

    // Verify audit log
    $auditLogs = AuditLog::where('event', 'license_provisioned')->get();

    expect($auditLogs)->toHaveCount(1);

    $auditLog = $auditLogs->first();
    expect($auditLog->actor_type)->toBe('brand')
        ->and($auditLog->metadata)->toHaveKey('products_count')
        ->and($auditLog->metadata['products_count'])->toBe(1);
});
