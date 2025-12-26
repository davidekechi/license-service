<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Models;

use App\Modules\AuditLog\Enums\AuditEventType;
use App\Modules\Shared\Core\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;
    use HasUlid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\AuditLogFactory
    {
        return \Database\Factories\AuditLogFactory::new();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_logs';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

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
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AuditLog $auditLog): void {
            $auditLog->ip_address = request()->ip();
            $auditLog->created_at = $auditLog->created_at ?? now();
        });
    }

    /**
     * Get the auditable model that this audit log belongs to.
     *
     * @return MorphTo<Model, $this>
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
