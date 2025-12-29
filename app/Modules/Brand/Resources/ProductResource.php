<?php

declare(strict_types=1);

namespace App\Modules\Brand\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->resource->public_id,
            'brand'     => [
                'public_id' => $this->resource->brand->public_id,
                'name'      => $this->resource->brand->name,
            ],
            'name'         => $this->resource->name,
            'slug'         => $this->resource->slug,
            'max_seats'    => $this->resource->max_seats,
            'is_unlimited' => $this->resource->hasUnlimitedSeats(),
            'is_active'    => $this->resource->is_active,
            'created_at'   => $this->resource->created_at?->toIso8601String()
        ];
    }
}
