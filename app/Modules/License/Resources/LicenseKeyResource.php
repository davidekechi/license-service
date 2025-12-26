<?php

declare(strict_types=1);

namespace App\Modules\License\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'license_key'    => $this->resource->key,
            'customer_email' => $this->resource->customer_email,
            'licenses'       => LicenseResource::collection($this->whenLoaded('licenses')),
            'created_at'     => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
