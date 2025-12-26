<?php

declare(strict_types=1);

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Models\LicenseKey;
use App\Modules\License\Services\LicenseKeyGenerator;

beforeEach(function () {
    $this->repository = app(LicenseKeyRepositoryInterface::class);
    $this->generator  = new LicenseKeyGenerator($this->repository);
});

test('generates unique license key for brand', function () {
    $brand = Brand::factory()->create(['slug' => 'rankmath']);

    $key = $this->generator->generate($brand);

    expect($key)->toBeString()
        ->and($key)->toStartWith('RANK-')
        ->and(\strlen($key))->toBe(19); // RANK-XXXX-XXXX-XXXX
});

test('generated key has correct format', function () {
    $brand = Brand::factory()->create(['slug' => 'wp-rocket']);

    $key = $this->generator->generate($brand);

    expect($key)->toMatch('/^WPRO-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/');
});

test('generates different keys for multiple calls', function () {
    $brand = Brand::factory()->create(['slug' => 'rankmath']);

    $key1 = $this->generator->generate($brand);
    $key2 = $this->generator->generate($brand);

    expect($key1)->not->toBe($key2);
});

test('validates correct license key format', function () {
    $validKey = 'RANK-ABCD-EFGH-IJKL';

    expect($this->generator->isValidFormat($validKey))->toBeTrue();
});

test('rejects invalid license key format', function () {
    $invalidKeys = [
        'RANK-ABC-EFGH-IJKL',  // Too short segment
        'rank-abcd-efgh-ijkl',  // Lowercase
        'RANK-ABCD-EFGH',       // Missing segment
        'RANKABCDEFGHIJKL',     // No dashes
    ];

    foreach ($invalidKeys as $key) {
        expect($this->generator->isValidFormat($key))->toBeFalse();
    }
});

test('throws exception when unable to generate unique key', function () {
    $brand = Brand::factory()->create(['slug' => 'test']);

    // Mock repository to always return existing key
    $mockRepo = Mockery::mock(LicenseKeyRepositoryInterface::class);
    $mockRepo->shouldReceive('findByKey')->andReturn(new LicenseKey());

    $generator = new LicenseKeyGenerator($mockRepo);

    $generator->generate($brand->slug);
})->throws(RuntimeException::class, 'Failed to generate unique license key');
