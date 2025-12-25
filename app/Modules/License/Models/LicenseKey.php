<?php

declare(strict_types=1);

namespace App\Modules\License\Models;

use App\Modules\Shared\Core\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    use HasFactory;
    use HasUlid;

    /**
     * The table associated with the model.
     */
    protected $table = 'license_keys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
     * Get the licenses for the license key.
     * Same module relationship - use Laravel relationship
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * Get active licenses only.
     */
    public function activeLicenses(): HasMany
    {
        return $this->licenses()->where('status', 'valid');
    }
}