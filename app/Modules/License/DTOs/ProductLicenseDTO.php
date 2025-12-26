<?php

declare(strict_types=1);

namespace App\Modules\License\DTOs;

class ProductLicenseDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public readonly string $productPublicId,
        public readonly ?string $expiresAt = null,
        public readonly int $maxActivations = 5
    ) {
    }

    /**
     * Create DTO from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productPublicId: $data['product_slug']   ?? $data['product_public_id'],
            expiresAt: $data['expires_at']           ?? null,
            maxActivations: $data['max_activations'] ?? 5
        );
    }

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_public_id' => $this->productPublicId,
            'expires_at'        => $this->expiresAt,
            'max_activations'   => $this->maxActivations,
        ];
    }
}
