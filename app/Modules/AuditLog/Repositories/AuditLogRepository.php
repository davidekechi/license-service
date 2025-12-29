<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Repositories;

use App\Modules\AuditLog\Contracts\AuditLogRepositoryInterface;
use App\Modules\AuditLog\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        private readonly AuditLog $model
    ) {
    }

    /**
     * Create a new audit log entry.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): AuditLog
    {
        return $this->model->create($data);
    }

    /**
     * Get audit logs for a specific auditable entity.
     *
     * @return Collection<int, AuditLog>
     */
    public function getByAuditable(string $auditableType, string $auditableId): Collection
    {
        return $this->model->where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get audit logs by event type.
     *
     * @return Collection<int, AuditLog>
     */
    public function getByEvent(string $event): Collection
    {
        return $this->model->where('event', $event)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get audit logs by actor.
     *
     * @return Collection<int, AuditLog>
     */
    public function getByActor(string $actorType, ?string $actorIdentifier = null): Collection
    {
        $query = $this->model->where('actor_type', $actorType);

        if ($actorIdentifier !== null) {
            $query->where('actor_identifier', $actorIdentifier);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get recent audit logs.
     *
     * @return Collection<int, AuditLog>
     */
    public function getRecent(int $limit = 100): Collection
    {
        return $this->model->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
