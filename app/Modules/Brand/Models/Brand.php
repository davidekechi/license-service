<?php

declare(strict_types=1);

namespace App\Modules\Brand\Models;

use App\Modules\Shared\Core\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $slug
 * @property string $api_key
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Brand extends Model
{
    use HasUlid;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'brands';

    protected $fillable = [
        'name',
        'slug',
        'api_key',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Generate a unique API key for the brand.
     */
    public static function generateApiKey(string $brandSlug): string
    {
        $prefix       = 'sk_live_' . $brandSlug . '_';
        $randomString = Str::random(64);

        return $prefix . \hash('sha256', $randomString);
    }

    /**
     * Check if brand is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
