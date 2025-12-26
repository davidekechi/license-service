<?php

declare(strict_types=1);

namespace App\Modules\License\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseActivationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id'           => $this->resource->public_id,
            'instance_identifier' => $this->resource->instance_identifier,
            'instance_type'       => $this->resource->instance_type->value,
            'instance_meta'       => $this->resource->instance_meta,
            'activated_at'        => $this->resource->activated_at?->toIso8601String(),
            'last_checked_at'     => $this->resource->last_checked_at?->toIso8601String(),
            'is_active'           => $this->resource->isActive(),
            'deactivated_at'      => $this->resource->deactivated_at?->toIso8601String(),
        ];
    }
}
