<?php

declare(strict_types=1);

namespace App\Modules\Shared\Core\Traits;

use Illuminate\Support\Str;

/**
 * Automatically generates ULIDs for models
 */
trait HasUlid
{
    /**
     * Boot the trait
     */
    protected static function bootHasUlid(): void
    {
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::ulid();
            }
        });
    }
}
