<?php

declare(strict_types=1);

namespace App\Modules\License\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id'       => $this->resource->public_id,
            'product_id'      => $this->resource->product_id,
            'status'          => $this->resource->status->value,
            'is_valid'        => $this->resource->isValid(),
            'is_expired'      => $this->resource->isExpired(),
            'expires_at'      => $this->resource->expires_at?->toIso8601String(),
            'max_activations' => $this->resource->max_activations,
            'is_unlimited'    => $this->resource->hasUnlimitedActivations(),
            'activations'     => LicenseActivationResource::collection($this->resource->whenLoaded('activations')),
            'created_at'      => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
