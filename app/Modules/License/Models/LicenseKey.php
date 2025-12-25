<?php

declare(strict_types=1);

namespace App\Modules\License\Models;

use App\Modules\Shared\Core\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property string $key
 * @property string $brand_id (ULID - cross-module reference)
 * @property string $customer_email
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class LicenseKey extends Model
{
    /** @use HasFactory<\Database\Factories\LicenseKeyFactory> */
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'license_keys';

    protected $fillable = [
        'key',
        'brand_id',
        'customer_email',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return HasMany<License, $this>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * @return HasMany<License, $this>
     */
    public function activeLicenses(): HasMany
    {
        return $this->licenses()->where('status', 'valid');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\LicenseKeyFactory
    {
        return \Database\Factories\LicenseKeyFactory::new();
    }
}
