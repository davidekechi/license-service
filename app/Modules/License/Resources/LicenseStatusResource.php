<?php

declare(strict_types=1);

namespace App\Modules\License\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseStatusResource extends JsonResource
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
            'licenses'       => $this->resource->licenses->map(function ($license) {
                return [
                    'id'         => $license->public_id,
                    'product_id' => $license->product_id,
                    'status'     => $license->status->value,
                    'is_valid'   => $license->isValid(),
                    'is_expired' => $license->isExpired(),
                    'expires_at' => $license->expires_at?->toIso8601String(),
                    'seats'      => [
                        'used'      => $license->activeActivations()->count(),
                        'available' => $license->hasUnlimitedActivations()
                            ? 'unlimited'
                            : \max(0, $license->max_activations - $license->activeActivations()->count()),
                        'total' => $license->hasUnlimitedActivations()
                            ? 'unlimited'
                            : $license->max_activations,
                    ],
                    'activations' => LicenseActivationResource::collection($license->activeActivations),
                ];
            }),
        ];
    }
}
