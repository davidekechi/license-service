<?php

declare(strict_types=1);

namespace App\Modules\Brand\DTOs;

class CreateBrandDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $apiKey = null,
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
            name: $data['name'],
            slug: $data['slug'],
            apiKey: $data['api_key']     ?? null,
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
            'name'      => $this->name,
            'slug'      => $this->slug,
            'api_key'   => $this->apiKey,
            'is_active' => $this->isActive,
        ];
    }
}
