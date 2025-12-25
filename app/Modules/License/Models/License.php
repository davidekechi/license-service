<?php

declare(strict_types=1);

namespace App\Modules\License\Models;

use App\Modules\License\Enums\LicenseStatus;
use App\Modules\Shared\Core\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property int $license_key_id
 * @property string $product_id (ULID - cross-module reference)
 * @property LicenseStatus $status
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int $max_activations
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read LicenseKey $licenseKey
 */
class License extends Model
{
    use HasUlid;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'licenses';

    protected $fillable = [
        'license_key_id',
        'product_id',
        'status',
        'expires_at',
        'max_activations',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'license_key_id'  => 'integer',
        'status'          => LicenseStatus::class,
        'expires_at'      => 'datetime',
        'max_activations' => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    /**
     * @return BelongsTo<LicenseKey, $this>
     */
    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class);
    }

    /**
     * @return HasMany<LicenseActivation, $this>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class);
    }

    /**
     * @return HasMany<LicenseActivation, $this>
     */
    public function activeActivations(): HasMany
    {
        return $this->activations()->whereNull('deactivated_at');
    }

    /**
     * Check if license is valid.
     */
    public function isValid(): bool
    {
        return $this->status === LicenseStatus::VALID && !$this->isExpired();
    }

    /**
     * Check if license is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false; // Lifetime license
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if license is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === LicenseStatus::SUSPENDED;
    }

    /**
     * Check if license is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === LicenseStatus::CANCELLED;
    }

    /**
     * Check if license has unlimited activations.
     */
    public function hasUnlimitedActivations(): bool
    {
        return $this->max_activations === -1;
    }

    /**
     * Get the number of available seats.
     */
    public function getAvailableSeatsAttribute(): int
    {
        if ($this->hasUnlimitedActivations()) {
            return PHP_INT_MAX;
        }

        $activeCount = $this->activeActivations()->count();

        return \max(0, $this->max_activations - $activeCount);
    }
}
