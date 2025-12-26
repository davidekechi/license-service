<?php

declare(strict_types=1);

namespace App\Modules\License\DTOs;

use Illuminate\Http\Request;

class ProvisionLicenseDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param array<int, ProductLicenseDTO> $products
     */
    public function __construct(
        public readonly string $customerEmail,
        public readonly array $products,
        public readonly ?string $licenseKey = null
    ) {
    }

    /**
     * Create DTO from request.
     */
    public static function fromRequest(Request $request): self
    {
        $products = \array_map(
            fn (array $product) => ProductLicenseDTO::fromArray($product),
            $request->input('products', [])
        );

        return new self(
            customerEmail: $request->input('customer_email'),
            products: $products,
            licenseKey: $request->input('license_key')
        );
    }

    /**
     * Create DTO from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $products = \array_map(
            fn (array $product) => ProductLicenseDTO::fromArray($product),
            $data['products'] ?? []
        );

        return new self(
            customerEmail: $data['customer_email'],
            products: $products,
            licenseKey: $data['license_key'] ?? null
        );
    }

    /**
     * Convert DTO to array for service layer.
     *
     * @return array<string, mixed>
     */
    public function toServiceArray(): array
    {
        return [
            'customer_email' => $this->customerEmail,
            'products'       => \array_map(
                fn (ProductLicenseDTO $product) => $product->toArray(),
                $this->products
            ),
            'license_key' => $this->licenseKey,
        ];
    }
}
