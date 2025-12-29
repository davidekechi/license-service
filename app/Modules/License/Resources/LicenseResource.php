<?php

declare(strict_types=1);

namespace App\Modules\License\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
{
    /** @var array<string, mixed>|null */
    private ?array $additionalData = null;

    /**
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Set additional data for the resource.
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public function withAdditionalData(array $data): static
    {
        $this->additionalData = $data;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get product and brand data loaded through service layer
        $product = $this->resource->getAttribute('_product')
            ?? $this->additionalData['product']
            ?? null;

        $brand = $this->resource->getAttribute('_brand')
            ?? $this->additionalData['brand']
            ?? null;

        return [
            'public_id' => $this->resource->public_id,
            'product'   => $product ? [
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
            'status'          => $this->resource->status->value,
            'is_valid'        => $this->resource->isValid(),
            'is_expired'      => $this->resource->isExpired(),
            'expires_at'      => $this->resource->expires_at?->toIso8601String(),
            'max_activations' => $this->resource->max_activations,
            'is_unlimited'    => $this->resource->hasUnlimitedActivations(),
            'activations'     => LicenseActivationResource::collection($this->whenLoaded('activations')),
            'created_at'      => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
