<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Contracts\LicenseRepositoryInterface;
use App\Modules\License\DTOs\ProductLicenseDTO;
use App\Modules\License\Enums\LicenseStatus;
use App\Modules\License\Models\LicenseKey;
use App\Modules\Shared\Events\LicenseProvisioned;
use Illuminate\Support\Facades\DB;

class ProvisionLicenseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository,
        private readonly LicenseRepositoryInterface $licenseRepository,
        private readonly LicenseKeyGenerator $licenseKeyGenerator,
        private readonly BrandService $brandService
    ) {
    }

    /**
     * Provision a new license or add to existing license key.
     *
     * @param array<int, ProductLicenseDTO|array<string, mixed>> $products
     */
    public function provision(
        string $brandPublicId,
        string $customerEmail,
        array $products,
        ?string $existingLicenseKey = null
    ): LicenseKey {
        return DB::transaction(function () use ($brandPublicId, $customerEmail, $products, $existingLicenseKey) {
            // Get brand to get slug for license key generation
            $brand = $this->brandService->findBrandByPublicId($brandPublicId);

            if ($brand === null) {
                throw new \InvalidArgumentException('Brand not found: ' . $brandPublicId);
            }

            // Convert arrays to DTOs if needed
            $productDtos = \array_map(
                fn ($product) => $product instanceof ProductLicenseDTO ? $product : ProductLicenseDTO::fromArray($product),
                $products
            );

            // Enrich products with max_seats from product if max_activations not provided
            $enrichedProducts = $this->enrichProductsWithMaxSeats($productDtos);

            // Get or create license key
            $licenseKey = $this->getOrCreateLicenseKey($brandPublicId, $brand->slug, $customerEmail, $existingLicenseKey);

            // Validate products belong to brand
            $productPublicIds = \array_map(fn (ProductLicenseDTO $product) => $product->productPublicId, $enrichedProducts);
            $this->validateProductsBelongToBrand($productPublicIds, $brandPublicId);

            // Create licenses for each product
            foreach ($enrichedProducts as $productDto) {
                $this->createLicense($licenseKey, $productDto->toArray());
            }

            // Reload with relationships
            $licenseKey = $this->licenseKeyRepository->findById($licenseKey->id) ?? $licenseKey;

            // Fire event for audit logging
            event(new LicenseProvisioned(
                licenseKey: $licenseKey,
                brandPublicId: $brandPublicId,
                metadata: [
                    'products_count' => \count($enrichedProducts),
                    'product_ids'    => $productPublicIds,
                    'is_new_key'     => $existingLicenseKey === null,
                ]
            ));

            return $licenseKey;
        });
    }

    /**
     * Enrich products with max_seats from product if max_activations not provided.
     *
     * @param array<int, ProductLicenseDTO> $products
     * @return array<int, ProductLicenseDTO>
     */
    private function enrichProductsWithMaxSeats(array $products): array
    {
        return \array_map(function (ProductLicenseDTO $productDto) {
            // If max_activations is not provided (null), fetch from product
            if ($productDto->maxActivations === null) {
                $product = $this->brandService->findProductByPublicId($productDto->productPublicId);

                if ($product === null) {
                    throw new \InvalidArgumentException('Product not found: ' . $productDto->productPublicId);
                }

                // Create new DTO with product's max_seats
                return new ProductLicenseDTO(
                    productPublicId: $productDto->productPublicId,
                    expiresAt: $productDto->expiresAt,
                    maxActivations: $product->max_seats
                );
            }

            return $productDto;
        }, $products);
    }

    /**
     * Get existing license key or create new one.
     */
    private function getOrCreateLicenseKey(
        string $brandPublicId,
        string $brandSlug,
        string $customerEmail,
        ?string $existingKey
    ): LicenseKey {
        if ($existingKey !== null) {
            $licenseKey = $this->licenseKeyRepository->findByKey($existingKey);

            if ($licenseKey === null) {
                throw new \InvalidArgumentException('License key not found: ' . $existingKey);
            }

            // Verify it belongs to the same brand and customer
            if ($licenseKey->brand_id !== $brandPublicId) {
                throw new \InvalidArgumentException('License key belongs to different brand');
            }

            if ($licenseKey->customer_email !== $customerEmail) {
                throw new \InvalidArgumentException('License key belongs to different customer');
            }

            return $licenseKey;
        }

        // Generate new license key
        $key = $this->licenseKeyGenerator->generate($brandSlug);

        return $this->licenseKeyRepository->create([
            'key'            => $key,
            'brand_id'       => $brandPublicId,
            'customer_email' => $customerEmail,
        ]);
    }

    /**
     * Validate that all products belong to the brand.
     *
     * @param array<string> $productPublicIds
     */
    private function validateProductsBelongToBrand(array $productPublicIds, string $brandPublicId): void
    {
        $isValid = $this->brandService->validateProductsBelongToBrand($productPublicIds, $brandPublicId);

        if (!$isValid) {
            throw new \InvalidArgumentException('One or more products do not belong to this brand');
        }
    }

    /**
     * Create a license for a product.
     *
     * @param array{product_public_id: string, expires_at: string|null, max_activations: int} $productData
     */
    private function createLicense(LicenseKey $licenseKey, array $productData): void
    {
        // Check if license already exists for this product
        $existing = $this->licenseRepository->findByLicenseKeyAndProduct(
            $licenseKey->id,
            $productData['product_public_id']
        );

        if ($existing !== null) {
            throw new \InvalidArgumentException(
                'License already exists for product: ' . $productData['product_public_id']
            );
        }

        $this->licenseRepository->create([
            'license_key_id'  => $licenseKey->id,
            'product_id'      => $productData['product_public_id'],
            'status'          => LicenseStatus::VALID,
            'expires_at'      => $productData['expires_at'] ?? null,
            'max_activations' => $productData['max_activations'],
        ]);
    }
}
