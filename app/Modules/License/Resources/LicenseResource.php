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
     * @param array<string, mixed>|null $additionalData
     */
    public function __construct($resource, ?array $additionalData = null)
    {
        parent::__construct($resource);
        $this->additionalData = $additionalData;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id'       => $this->resource->public_id,
            'product'         => $this->additionalData['product'] ?? null,
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
