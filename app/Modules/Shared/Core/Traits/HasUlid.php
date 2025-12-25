<?php

declare(strict_types=1);

namespace App\Modules\Shared\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Automatically generates ULIDs for models
 *
 * @phpstan-ignore-next-line trait.unused
 */
trait HasUlid
{
    /**
     * Boot the trait
     */
    protected static function bootHasUlid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::ulid();
            }
        });
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
