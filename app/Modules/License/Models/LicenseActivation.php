<?php

declare(strict_types=1);

namespace App\Modules\License\Models;

use App\Modules\License\Enums\InstanceType;
use App\Modules\Shared\Core\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_id
 * @property int $license_id
 * @property string $instance_identifier
 * @property InstanceType $instance_type
 * @property array<string, mixed>|null $instance_meta
 * @property \Illuminate\Support\Carbon $activated_at
 * @property \Illuminate\Support\Carbon|null $last_checked_at
 * @property \Illuminate\Support\Carbon|null $deactivated_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read License $license
 */
class LicenseActivation extends Model
{
    use HasFactory;
    use HasUlid;

    /**
     * The table associated with the model.
     */
    protected $table = 'license_activations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'license_id',
        'instance_identifier',
        'instance_type',
        'instance_meta',
        'activated_at',
        'last_checked_at',
        'deactivated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'license_id' => 'integer',
        'instance_type' => InstanceType::class,
        'instance_meta' => 'array',
        'activated_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the license that owns the activation.
     * Same module relationship - use Laravel relationship
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /**
     * Check if activation is active (not deactivated).
     */
    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    /**
     * Check if activation is stale (last checked > 90 days ago).
     */
    public function isStale(): bool
    {
        if ($this->last_checked_at === null) {
            return true;
        }

        return $this->last_checked_at->diffInDays(now()) > 90;
    }

    /**
     * Deactivate the activation.
     */
    public function deactivate(): bool
    {
        $this->deactivated_at = now();
        return $this->save();
    }

    /**
     * Update last checked timestamp (heartbeat).
     */
    public function updateHeartbeat(): bool
    {
        $this->last_checked_at = now();
        return $this->save();
    }
}