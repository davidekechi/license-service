<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Contracts;

use App\Modules\AuditLog\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;

interface AuditLogRepositoryInterface
{
    /**
     * Create a new audit log entry.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): AuditLog;

    /**
     * Get audit logs for a specific auditable entity.
     *
     * @return Collection<int, AuditLog>
     */
    public function getByAuditable(string $auditableType, string $auditableId): Collection;

    /**
     * Get audit logs by event type.
     *
     * @return Collection<int, AuditLog>
     */
    public function getByEvent(string $event): Collection;

    /**
     * Get audit logs by actor.
     *
     * @return Collection<int, AuditLog>
     */
    public function getByActor(string $actorType, ?string $actorIdentifier = null): Collection;
}
