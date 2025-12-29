<?php

declare(strict_types=1);

namespace App\Modules\Brand\Models;

use App\Modules\Shared\Core\Traits\HasUlid;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property int $brand_id
 * @property string $name
 * @property string $slug
 * @property int $max_seats
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Brand $brand
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'products';

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'max_seats',
        'is_active',
    ];

    protected $casts = [
        'brand_id'   => 'integer',
        'max_seats'  => 'integer',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Check if product is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if product has unlimited seats.
     */
    public function hasUnlimitedSeats(): bool
    {
        return $this->max_seats === -1;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
