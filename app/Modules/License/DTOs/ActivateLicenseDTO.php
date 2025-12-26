<?php

declare(strict_types=1);

namespace App\Modules\License\DTOs;

use App\Modules\License\Enums\InstanceType;
use Illuminate\Http\Request;

class ActivateLicenseDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param array<string, mixed>|null $instanceMeta
     */
    public function __construct(
        public readonly string $instanceIdentifier,
        public readonly InstanceType $instanceType,
        public readonly string $productSlug,
        public readonly ?array $instanceMeta = null
    ) {
    }

    /**
     * Create DTO from request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            instanceIdentifier: $request->input('instance_identifier'),
            instanceType: InstanceType::from($request->input('instance_type', 'site')),
            productSlug: $request->input('product_slug'),
            instanceMeta: $request->input('instance_meta')
        );
    }

    /**
     * Create DTO from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            instanceIdentifier: $data['instance_identifier'],
            instanceType: InstanceType::from($data['instance_type'] ?? 'site'),
            productSlug: $data['product_slug'],
            instanceMeta: $data['instance_meta'] ?? null
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
            'instance_identifier' => $this->instanceIdentifier,
            'instance_type'       => $this->instanceType->value,
            'product_slug'        => $this->productSlug,
            'instance_meta'       => $this->instanceMeta,
        ];
    }
}
