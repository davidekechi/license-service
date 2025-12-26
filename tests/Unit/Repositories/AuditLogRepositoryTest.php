<?php

declare(strict_types=1);

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\AuditLog\Repositories\AuditLogRepository;
use App\Modules\License\Models\License;

beforeEach(function () {
    $this->repository = new AuditLogRepository(new AuditLog());
});

test('can create an audit log', function () {
    $license = License::factory()->create();

    $data = [
        'auditable_type'   => License::class,
        'auditable_id'     => $license->public_id,
        'event'            => 'license_provisioned',
        'actor_type'       => 'brand',
        'actor_identifier' => 'rankmath',
        'metadata'         => ['key' => 'value'],
        'ip_address'       => '127.0.0.1',
    ];

    $auditLog = $this->repository->create($data);

    expect($auditLog)->toBeModel(AuditLog::class)
        ->and($auditLog->event)->toBe('license_provisioned')
        ->and($auditLog->actor_type)->toBe('brand');
});

test('can get audit logs by auditable', function () {
    $license = License::factory()->create();

    AuditLog::factory()->count(3)->create([
        'auditable_type' => License::class,
        'auditable_id'   => $license->public_id,
    ]);
    AuditLog::factory()->count(2)->create(); // Different auditable

    $logs = $this->repository->getByAuditable(License::class, $license->public_id);

    expect($logs)->toHaveCount(3);
});

test('can get audit logs by event', function () {
    AuditLog::factory()->count(3)->create(['event' => 'license_provisioned']);
    AuditLog::factory()->count(2)->create(['event' => 'license_activated']);

    $logs = $this->repository->getByEvent('license_provisioned');

    expect($logs)->toHaveCount(3);
});

test('can get audit logs by actor type', function () {
    AuditLog::factory()->count(3)->create(['actor_type' => 'brand']);
    AuditLog::factory()->count(2)->create(['actor_type' => 'product']);

    $logs = $this->repository->getByActor('brand');

    expect($logs)->toHaveCount(3);
});

test('can get audit logs by actor type and identifier', function () {
    AuditLog::factory()->count(2)->create([
        'actor_type'       => 'brand',
        'actor_identifier' => 'rankmath',
    ]);
    AuditLog::factory()->count(1)->create([
        'actor_type'       => 'brand',
        'actor_identifier' => 'wp-rocket',
    ]);

    $logs = $this->repository->getByActor('brand', 'rankmath');

    expect($logs)->toHaveCount(2);
});

test('can get recent audit logs with limit', function () {
    AuditLog::factory()->count(150)->create();

    $logs = $this->repository->getRecent(100);

    expect($logs)->toHaveCount(100);
});
