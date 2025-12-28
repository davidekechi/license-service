<?php

declare(strict_types=1);

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Brand\Models\Brand;
use App\Modules\License\Enums\LicenseStatus;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseActivation;
use App\Modules\License\Models\LicenseKey;

beforeEach(function () {
    $this->brand   = Brand::factory()->create(['is_active' => true]);
    $this->product = $this->brand->products()->create([
        'name'      => 'Test Product',
        'slug'      => 'test-product',
        'max_seats' => 5,
        'is_active' => true,
    ]);

    $this->licenseKey = LicenseKey::factory()->create([
        'brand_id'       => $this->brand->public_id,
        'customer_email' => 'customer@example.com',
        'key'            => 'TEST-1234-5678-9ABC',
    ]);

    $this->license = License::factory()->create([
        'license_key_id'  => $this->licenseKey->id,
        'product_id'      => $this->product->public_id,
        'status'          => LicenseStatus::VALID,
        'expires_at'      => now()->addYear(),
        'max_activations' => 5,
    ]);
});

test('can activate license on valid instance', function () {
    $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'statusCode',
            'success',
            'message',
            'data' => [
                'public_id',
                'instance_identifier',
                'instance_type',
                'activated_at',
                'is_active',
            ],
        ]);

    expect($response->json('success'))->toBeTrue()
        ->and($response->json('data.instance_identifier'))->toBe('https://example.com')
        ->and($response->json('data.is_active'))->toBeTrue();
});

test('activation consumes a seat', function () {
    $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    $activeCount = LicenseActivation::where('license_id', $this->license->id)
        ->whereNull('deactivated_at')
        ->count();

    expect($activeCount)->toBe(1);
});

test('rejects activation when no seats available', function () {
    // Create max activations
    LicenseActivation::factory()->count(5)->create([
        'license_id' => $this->license->id,
    ]);

    $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://new-site.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'License has reached its maximum activation limit. 5 of 5 seats used.');
});

test('rejects activation on expired license', function () {
    $this->license->update(['expires_at' => now()->subDay()]);

    $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false);

    expect($response->json('message'))->toContain('expired');
});

test('rejects activation on suspended license', function () {
    $this->license->update(['status' => LicenseStatus::SUSPENDED]);

    $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'License is currently suspended');
});

test('rejects activation on cancelled license', function () {
    $this->license->update(['status' => LicenseStatus::CANCELLED]);

    $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'License has been cancelled');
});

test('handles different instance types', function () {
    $types = [
        ['type' => 'site', 'identifier' => 'https://example.com'],
        ['type' => 'device', 'identifier' => 'device-uuid-12345'],
        ['type' => 'server', 'identifier' => '192.168.1.100'],
    ];

    foreach ($types as $instance) {
        $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
            'instance_identifier' => $instance['identifier'],
            'instance_type'       => $instance['type'],
            'product_public_id'   => $this->product->public_id,
        ]);

        $response->assertStatus(201);
    }

    expect(LicenseActivation::count())->toBe(3);
});

test('duplicate activation is idempotent', function () {
    // First activation
    $firstResponse = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    $firstId = $firstResponse->json('data.id');

    // Second activation (same instance)
    $secondResponse = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    $secondId = $secondResponse->json('data.id');

    expect($firstId)->toBe($secondId)
        ->and(LicenseActivation::count())->toBe(1);
});

test('can reactivate previously deactivated instance', function () {
    // Create deactivated activation
    $activation = LicenseActivation::factory()->deactivated()->create([
        'license_id'          => $this->license->id,
        'instance_identifier' => 'https://example.com',
    ]);

    $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    $response->assertStatus(201);

    $activation->refresh();
    expect($activation->deactivated_at)->toBeNull()
        ->and($activation->isActive())->toBeTrue();
});

test('validates required fields', function () {
    $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        // Missing required fields
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['instance_identifier', 'instance_type', 'product_public_id']);
});

test('validates instance type enum', function () {
    $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'invalid-type',
        'product_public_id'   => $this->product->public_id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['instance_type']);
});

test('returns 404 for non-existent license key', function () {
    $response = $this->postJson('/api/v1/licenses/FAKE-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    $response->assertStatus(404)
        ->assertJsonPath('success', false);
});

test('returns 404 for product not in license key', function () {
    $otherProduct = $this->brand->products()->create([
        'name'      => 'Other Product',
        'slug'      => 'other-product',
        'max_seats' => 5,
    ]);

    $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $otherProduct->public_id,
    ]);

    $response->assertStatus(404)
        ->assertJsonPath('message', 'License not found: No license found for product: ' . $otherProduct->public_id);
});

test('creates audit log on successful activation', function () {
    $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
    ]);

    // Process queue
    $this->artisan('queue:work --once');

    $auditLog = AuditLog::where('event', 'license_activated')->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->actor_type)->toBe('product')
        ->and($auditLog->metadata)->toHaveKey('instance_identifier');
});

test('supports instance metadata', function () {
    $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
        'product_public_id'   => $this->product->public_id,
        'instance_meta'       => [
            'ip'         => '192.168.1.1',
            'user_agent' => 'WordPress/6.0',
        ],
    ]);

    $response->assertStatus(201);

    $activation = LicenseActivation::first();
    expect($activation->instance_meta)->toHaveKey('ip')
        ->and($activation->instance_meta['ip'])->toBe('192.168.1.1');
});

test('allows unlimited activations for unlimited licenses', function () {
    $this->license->update(['max_activations' => -1]);

    // Create 100 activations
    for ($i = 0; $i < 100; $i++) {
        $response = $this->postJson('/api/v1/licenses/TEST-1234-5678-9ABC/activate', [
            'instance_identifier' => 'https://site-' . $i . '.com',
            'instance_type'       => 'site',
            'product_public_id'   => $this->product->public_id,
        ]);

        $response->assertStatus(201);
    }

    expect(LicenseActivation::count())->toBe(100);
});
