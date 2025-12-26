<?php

declare(strict_types=1);

namespace App\Modules\Brand\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id'  => $this->resource->public_id,
            'name'       => $this->resource->name,
            'slug'       => $this->resource->slug,
            'is_active'  => $this->resource->is_active,
            'created_at' => $this->resource->created_at?->toIso8601String()
        ];
    }
}
