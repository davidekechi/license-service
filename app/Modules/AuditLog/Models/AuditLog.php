<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Models;

use App\Modules\AuditLog\Enums\AuditEventType;
use App\Modules\Shared\Core\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $public_id
 * @property string $auditable_type
 * @property string $auditable_id (ULID - cross-module polymorphic)
 * @property string $event
 * @property string $actor_type
 * @property string|null $actor_identifier
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $created_at
 */
class AuditLog extends Model
{
    use HasUlid;

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_logs';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'event',
        'actor_type',
        'actor_identifier',
        'metadata',
        'ip_address',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AuditLog $auditLog): void {
            if ($auditLog->created_at === null) {
                $auditLog->created_at = now();
            }

            if ($auditLog->ip_address === null && request()) {
                $auditLog->ip_address = request()->ip();
            }
        });
    }

    /**
     * Get the parent auditable model (polymorphic).
     * Note: We're using ULID for cross-module polymorphic relations
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'auditable_type', 'auditable_id', 'public_id');
    }

    /**
     * Get event type enum.
     */
    public function getEventTypeAttribute(): ?AuditEventType
    {
        return AuditEventType::tryFrom($this->event);
    }
}