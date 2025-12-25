<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\Brand\Models\Brand;
use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Contracts\LicenseRepositoryInterface;
use App\Modules\License\Enums\LicenseStatus;
use App\Modules\License\Models\LicenseKey;
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
     * @param array<int, array{product_public_id: string, expires_at: string|null, max_activations: int}> $products
     */
    public function provision(
        Brand $brand,
        string $customerEmail,
        array $products,
        ?string $existingLicenseKey = null
    ): LicenseKey {
        return DB::transaction(function () use ($brand, $customerEmail, $products, $existingLicenseKey) {
            // Get or create license key
            $licenseKey = $this->getOrCreateLicenseKey($brand, $customerEmail, $existingLicenseKey);

            // Validate products belong to brand
            $productPublicIds = \array_column($products, 'product_public_id');
            $this->validateProductsBelongToBrand($productPublicIds, $brand->public_id);

            // Create licenses for each product
            foreach ($products as $productData) {
                $this->createLicense($licenseKey, $productData);
            }

            // Reload with relationships
            return $this->licenseKeyRepository->findById($licenseKey->id) ?? $licenseKey;
        });
    }

    /**
     * Get existing license key or create new one.
     */
    private function getOrCreateLicenseKey(
        Brand $brand,
        string $customerEmail,
        ?string $existingKey
    ): LicenseKey {
        if ($existingKey !== null) {
            $licenseKey = $this->licenseKeyRepository->findByKey($existingKey);

            if ($licenseKey === null) {
                throw new \InvalidArgumentException('License key not found: ' . $existingKey);
            }

            // Verify it belongs to the same brand and customer
            if ($licenseKey->brand_id !== $brand->public_id) {
                throw new \InvalidArgumentException('License key belongs to different brand');
            }

            if ($licenseKey->customer_email !== $customerEmail) {
                throw new \InvalidArgumentException('License key belongs to different customer');
            }

            return $licenseKey;
        }

        // Generate new license key
        $key = $this->licenseKeyGenerator->generate($brand);

        return $this->licenseKeyRepository->create([
            'key'            => $key,
            'brand_id'       => $brand->public_id,
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
