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
                $activeCount = $license->activations->count();

                // Get product and brand data loaded through service layer
                $product = $license->getAttribute('_product');
                $brand   = $license->getAttribute('_brand');

                return [
                    'product' => $product ? [
                        'public_id' => $product['public_id'] ?? null,
                        'name'      => $product['name']      ?? null,
                        'slug'      => $product['slug']      ?? null,
                        'max_seats' => $product['max_seats'] ?? null,
                        'is_active' => $product['is_active'] ?? null,
                    ] : null,
                    'brand' => $brand ? [
                        'public_id' => $brand['public_id'] ?? null,
                        'name'      => $brand['name']      ?? null,
                    ] : null,
                    'status'     => $license->status->value,
                    'is_valid'   => $license->isValid(),
                    'is_expired' => $license->isExpired(),
                    'expires_at' => $license->expires_at?->toISOString(),
                    'seats'      => [
                        'used'      => $activeCount,
                        'available' => $license->hasUnlimitedActivations()
                            ? 'unlimited'
                            : \max(0, $license->max_activations - $activeCount),
                        'total' => $license->hasUnlimitedActivations()
                            ? 'unlimited'
                            : $license->max_activations,
                    ],
                    'activations' => LicenseActivationResource::collection($license->activations),
                ];
            }),
        ];
    }
}
