<?php

declare(strict_types=1);

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Brand\Models\Brand;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseActivation;
use App\Modules\License\Models\LicenseKey;

test('complete activation workflow with seat management', function () {
    // Setup
    $brand   = Brand::factory()->create(['is_active' => true]);
    $product = $brand->products()->create([
        'name'      => 'WP Rocket',
        'slug'      => 'wp-rocket',
        'max_seats' => 3,
    ]);

    // Provision license
    $provisionResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            [
                'product_public_id' => $product->public_id,
                'max_activations'   => 3,
            ],
        ],
    ]);

    $licenseKey = $provisionResponse->json('data.license_key');

    // Activate on 3 sites
    $sites = ['site1.com', 'site2.com', 'site3.com'];
    foreach ($sites as $site) {
        $response = $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
            'instance_identifier' => "https://{$site}",
            'instance_type'       => 'site',
            'product_public_id'   => $product->public_id,
        ]);

        $response->assertStatus(201);
    }

    // 4th activation should fail (no seats)
    $response = $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site4.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('message', 'License has reached its maximum activation limit. 3 of 3 seats used.');

    // Verify database state
    $license = License::where('product_id', $product->public_id)->first();
    expect($license->activeActivations)->toHaveCount(3);
});

test('multi-product activation on same license key', function () {
    // Setup
    $brand    = Brand::factory()->create(['is_active' => true]);
    $product1 = $brand->products()->create([
        'name'      => 'Product 1',
        'slug'      => 'product-1',
        'max_seats' => 5,
    ]);
    $product2 = $brand->products()->create([
        'name'      => 'Product 2',
        'slug'      => 'product-2',
        'max_seats' => 3,
    ]);

    // Provision with both products
    $provisionResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $brand->api_key,
    ])->postJson('/api/v1/brands/licenses/provision', [
        'customer_email' => 'customer@example.com',
        'products'       => [
            ['product_public_id' => $product1->public_id, 'max_activations' => 5],
            ['product_public_id' => $product2->public_id, 'max_activations' => 3],
        ],
    ]);

    $licenseKey = $provisionResponse->json('data.license_key');

    // Activate Product 1
    $response1 = $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product1->public_id,
    ]);

    $response1->assertStatus(201);

    // Activate Product 2 on same site
    $response2 = $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://site.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product2->public_id,
    ]);

    $response2->assertStatus(201);

    // Verify separate activations
    $licenseKeyRecord = LicenseKey::where('key', $licenseKey)->first();
    expect($licenseKeyRecord->licenses)->toHaveCount(2);

    $license1 = $licenseKeyRecord->licenses->where('product_id', $product1->public_id)->first();
    $license2 = $licenseKeyRecord->licenses->where('product_id', $product2->public_id)->first();

    expect($license1->activations)->toHaveCount(1)
        ->and($license2->activations)->toHaveCount(1);
});

test('activation creates complete audit trail', function () {
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

    // Activate
    $this->postJson("/api/v1/licenses/{$licenseKey}/activate", [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ]);

    // Process queued events
    $this->artisan('queue:work --stop-when-empty');

    // Check audit logs
    $provisionLog  = AuditLog::where('event', 'license_provisioned')->first();
    $activationLog = AuditLog::where('event', 'license_activated')->first();

    expect($provisionLog)->not->toBeNull()
        ->and($provisionLog->actor_type)->toBe('brand')
        ->and($activationLog)->not->toBeNull()
        ->and($activationLog->actor_type)->toBe('product')
        ->and($activationLog->metadata)->toHaveKey('instance_identifier');
});

test('deactivated instance can be reactivated and consumes seat again', function () {
    // Setup
    $brand   = Brand::factory()->create();
    $product = $brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 2,
    ]);
    $licenseKey = LicenseKey::factory()->create(['brand_id' => $brand->public_id]);
    $license    = License::factory()->create([
        'license_key_id'  => $licenseKey->id,
        'product_id'      => $product->public_id,
        'max_activations' => 2,
    ]);

    // Create deactivated activation
    $oldActivation = LicenseActivation::factory()->deactivated()->create([
        'license_id'          => $license->id,
        'instance_identifier' => 'https://old-site.com',
    ]);

    // Activate new site (1 seat used)
    $this->postJson("/api/v1/licenses/{$licenseKey->key}/activate", [
        'instance_identifier' => 'https://new-site.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ])->assertStatus(201);

    // Reactivate old site (should work - 2 seats used)
    $this->postJson("/api/v1/licenses/{$licenseKey->key}/activate", [
        'instance_identifier' => 'https://old-site.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ])->assertStatus(201);

    // Verify 2 active activations
    expect($license->activeActivations)->toHaveCount(2);

    // 3rd activation should fail
    $this->postJson("/api/v1/licenses/{$licenseKey->key}/activate", [
        'instance_identifier' => 'https://third-site.com',
        'instance_type'       => 'site',
        'product_public_id'   => $product->public_id,
    ])->assertStatus(403);
});
