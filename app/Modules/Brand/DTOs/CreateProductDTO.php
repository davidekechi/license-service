<?php

declare(strict_types=1);

namespace App\Modules\Brand\DTOs;

class CreateProductDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public readonly int $brandId,
        public readonly string $name,
        public readonly string $slug,
        public readonly int $maxSeats = 5,
        public readonly bool $isActive = true
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
            brandId: $data['brand_id'],
            name: $data['name'],
            slug: $data['slug'],
            maxSeats: $data['max_seats'] ?? 5,
            isActive: $data['is_active'] ?? true
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
            'brand_id'  => $this->brandId,
            'name'      => $this->name,
            'slug'      => $this->slug,
            'max_seats' => $this->maxSeats,
            'is_active' => $this->isActive,
        ];
    }
}
