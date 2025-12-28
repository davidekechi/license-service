<?php

declare(strict_types=1);

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

test('can retrieve license status', function () {
    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $response->assertStatus(200)
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
        ->and($response->json('data.license_key'))->toBe('TEST-1234-5678-9ABC')
        ->and($response->json('data.customer_email'))->toBe('customer@example.com');
});

test('status shows all licenses for the key', function () {
    // Add second product
    $product2 = $this->brand->products()->create([
        'name'      => 'Second Product',
        'slug'      => 'second-product',
        'max_seats' => 3,
    ]);

    License::factory()->create([
        'license_key_id'  => $this->licenseKey->id,
        'product_id'      => $product2->public_id,
        'status'          => LicenseStatus::VALID,
        'max_activations' => 3,
    ]);

    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    expect($response->json('data.licenses'))->toHaveCount(2);
});

test('status shows correct seat availability', function () {
    // Create 2 active activations
    LicenseActivation::factory()->count(2)->create([
        'license_id' => $this->license->id,
    ]);

    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $licenseData = $response->json('data.licenses.0');

    expect($licenseData['seats']['used'])->toBe(2)
        ->and($licenseData['seats']['available'])->toBe(3)
        ->and($licenseData['seats']['total'])->toBe(5);
});

test('status shows active activations only', function () {
    // Create 3 active activations
    LicenseActivation::factory()->count(3)->create([
        'license_id' => $this->license->id,
    ]);

    // Create 2 deactivated activations
    LicenseActivation::factory()->count(2)->deactivated()->create([
        'license_id' => $this->license->id,
    ]);

    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $licenseData = $response->json('data.licenses.0');

    expect($licenseData['activations'])->toHaveCount(3)
        ->and($licenseData['seats']['used'])->toBe(3);
});

test('status shows unlimited seats correctly', function () {
    $this->license->update(['max_activations' => -1]);

    // Create 100 activations
    LicenseActivation::factory()->count(100)->create([
        'license_id' => $this->license->id,
    ]);

    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $licenseData = $response->json('data.licenses.0');

    expect($licenseData['seats']['used'])->toBe(100)
        ->and($licenseData['seats']['available'])->toBe('unlimited')
        ->and($licenseData['seats']['total'])->toBe('unlimited');
});

test('status shows license validation status', function () {
    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $licenseData = $response->json('data.licenses.0');

    expect($licenseData['is_valid'])->toBeTrue()
        ->and($licenseData['is_expired'])->toBeFalse()
        ->and($licenseData['status'])->toBe('valid');
});

test('status shows expired license correctly', function () {
    $this->license->update(['expires_at' => now()->subDay()]);

    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $licenseData = $response->json('data.licenses.0');

    expect($licenseData['is_valid'])->toBeFalse()
        ->and($licenseData['is_expired'])->toBeTrue();
});

test('status shows suspended license correctly', function () {
    $this->license->update(['status' => LicenseStatus::SUSPENDED]);

    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $licenseData = $response->json('data.licenses.0');

    expect($licenseData['is_valid'])->toBeFalse()
        ->and($licenseData['status'])->toBe('suspended');
});

test('status shows cancelled license correctly', function () {
    $this->license->update(['status' => LicenseStatus::CANCELLED]);

    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $licenseData = $response->json('data.licenses.0');

    expect($licenseData['is_valid'])->toBeFalse()
        ->and($licenseData['status'])->toBe('cancelled');
});

test('status includes activation details', function () {
    $activation = LicenseActivation::factory()->create([
        'license_id'          => $this->license->id,
        'instance_identifier' => 'https://example.com',
        'instance_type'       => 'site',
    ]);

    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $activationData = $response->json('data.licenses.0.activations.0');

    expect($activationData['instance_identifier'])->toBe('https://example.com')
        ->and($activationData['instance_type'])->toBe('site')
        ->and($activationData['is_active'])->toBeTrue();
});

test('status returns 404 for non-existent license key', function () {
    $response = $this->getJson('/api/v1/licenses/FAKE-1234-5678-9ABC/status');

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'License key not found');
});

test('status updates heartbeat for active activations', function () {
    $activation = LicenseActivation::factory()->create([
        'license_id'      => $this->license->id,
        'last_checked_at' => now()->subHour(),
    ]);

    $oldHeartbeat = $activation->last_checked_at;

    // Wait a moment to ensure timestamp difference
    \sleep(1);

    $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $activation->refresh();

    expect($activation->last_checked_at)->not->toEqual($oldHeartbeat)
        ->and($activation->last_checked_at)->toBeGreaterThan($oldHeartbeat);
});

test('status does not update heartbeat for deactivated activations', function () {
    $activation = LicenseActivation::factory()->deactivated()->create([
        'license_id'      => $this->license->id,
        'last_checked_at' => now()->subHour(),
    ]);

    $oldHeartbeat = $activation->last_checked_at;

    $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $activation->refresh();

    expect($activation->last_checked_at->timestamp)->toBe($oldHeartbeat->timestamp);
});

test('status shows expiration date in ISO format', function () {
    $expiresAt = now()->addMonths(6);
    $this->license->update(['expires_at' => $expiresAt]);

    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $licenseData = $response->json('data.licenses.0');

    expect($licenseData['expires_at'])->toContain('T')
        ->and($licenseData['expires_at'])->toContain('Z');
});

test('status handles license with no activations', function () {
    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $licenseData = $response->json('data.licenses.0');

    expect($licenseData['activations'])->toBeArray()
        ->and($licenseData['activations'])->toHaveCount(0)
        ->and($licenseData['seats']['used'])->toBe(0)
        ->and($licenseData['seats']['available'])->toBe(5);
});

test('status shows multiple products with different seat usage', function () {
    // Add second product with different seats
    $product2 = $this->brand->products()->create([
        'name'      => 'Second Product',
        'slug'      => 'second-product',
        'max_seats' => 3,
    ]);

    $license2 = License::factory()->create([
        'license_key_id'  => $this->licenseKey->id,
        'product_id'      => $product2->public_id,
        'max_activations' => 3,
    ]);

    // License 1: 2/5 seats used
    LicenseActivation::factory()->count(2)->create([
        'license_id' => $this->license->id,
    ]);

    // License 2: 3/3 seats used
    LicenseActivation::factory()->count(3)->create([
        'license_id' => $license2->id,
    ]);

    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $licenses = $response->json('data.licenses');

    expect($licenses[0]['seats']['used'])->toBe(2)
        ->and($licenses[0]['seats']['available'])->toBe(3)
        ->and($licenses[1]['seats']['used'])->toBe(3)
        ->and($licenses[1]['seats']['available'])->toBe(0);
});

test('status is accessible without authentication', function () {
    // No headers, public endpoint
    $response = $this->getJson('/api/v1/licenses/TEST-1234-5678-9ABC/status');

    $response->assertStatus(200);
});
