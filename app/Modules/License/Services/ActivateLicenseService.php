<?php

declare(strict_types=1);

namespace App\Modules\License\Services;

use App\Modules\License\Contracts\LicenseActivationRepositoryInterface;
use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Contracts\LicenseRepositoryInterface;
use App\Modules\License\DTOs\ActivateLicenseDTO;
use App\Modules\License\Enums\InstanceType;
use App\Modules\License\Models\License;
use App\Modules\License\Models\LicenseActivation;
use App\Modules\License\Models\LicenseKey;
use App\Modules\Shared\Events\LicenseActivated;
use App\Modules\Shared\Support\Exceptions\LicenseExpiredException;
use App\Modules\Shared\Support\Exceptions\LicenseInvalidException;
use App\Modules\Shared\Support\Exceptions\LicenseNotFoundException;
use App\Modules\Shared\Support\Exceptions\SeatLimitExceededException;
use Illuminate\Support\Facades\DB;

class ActivateLicenseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly LicenseKeyRepositoryInterface $licenseKeyRepository,
        private readonly LicenseRepositoryInterface $licenseRepository,
        private readonly LicenseActivationRepositoryInterface $activationRepository,
        private readonly LicenseValidationService $validationService,
        private readonly SeatManagementService $seatManagementService,
        private readonly BrandService $brandService
    ) {
    }

    /**
     * Activate a license for a specific instance.
     */
    public function activate(string $licenseKeyString, ActivateLicenseDTO $dto): LicenseActivation
    {
        return DB::transaction(function () use ($licenseKeyString, $dto) {
            // Find license key
            $licenseKey = $this->licenseKeyRepository->findByKey($licenseKeyString);

            if ($licenseKey === null) {
                throw new LicenseNotFoundException($licenseKeyString);
            }

            // Find product to get product_id
            $product = $this->brandService->findProductByPublicId($dto->productSlug);

            if ($product === null) {
                throw new LicenseNotFoundException('Product not found: ' . $dto->productSlug);
            }

            // Find the specific license for this product
            $license = $this->licenseRepository->findByLicenseKeyAndProduct(
                $licenseKey->id,
                $product->public_id
            );

            if ($license === null) {
                throw new LicenseNotFoundException(
                    'No license found for product: ' . $dto->productSlug
                );
            }

            // Validate license can be activated
            $this->validationService->validateForActivation($license);

            // Check if already activated on this instance (idempotent)
            $existingActivation = $this->activationRepository->findByLicenseAndInstance(
                $license->id,
                $dto->instanceIdentifier
            );

            if ($existingActivation !== null) {
                // If already deactivated, we can reactivate
                if (!$existingActivation->isActive()) {
                    return $this->reactivateExisting($existingActivation, $licenseKeyString);
                }

                // Already active - return existing (idempotent)
                return $existingActivation;
            }

            // Check seat availability
            if (!$this->seatManagementService->hasAvailableSeats($license)) {
                $seatInfo = $this->seatManagementService->getSeatInfo($license);
                throw new SeatLimitExceededException(
                    (int) $seatInfo['used'],
                    (int) $seatInfo['total']
                );
            }

            // Create activation
            $activation = $this->activationRepository->create([
                'license_id' => $license->id,
                'instance_identifier' => $dto->instanceIdentifier,
                'instance_type' => $dto->instanceType,
                'instance_meta' => $dto->instanceMeta,
                'activated_at' => now(),
                'last_checked_at' => now(),
            ]);

            // Fire event for audit logging
            event(new LicenseActivated(
                activation: $activation->load('license'),
                licenseKeyString: $licenseKeyString,
                metadata: [
                    'seats_used' => $this->seatManagementService->getActiveSeatCount($license),
                    'seats_total' => $license->max_activations,
                    'product_slug' => $dto->productSlug,
                ]
            ));

            return $activation;
        });
    }

    /**
     * Reactivate a previously deactivated activation.
     */
    private function reactivateExisting(
        LicenseActivation $activation,
        string $licenseKeyString
    ): LicenseActivation {
        // Update activation
        $updated = $this->activationRepository->update($activation, [
            'deactivated_at' => null,
            'activated_at' => now(),
            'last_checked_at' => now(),
        ]);

        // Fire event
        event(new LicenseActivated(
            activation: $updated->load('license'),
            licenseKeyString: $licenseKeyString,
            metadata: [
                'reactivation' => true,
                'seats_used' => $this->seatManagementService->getActiveSeatCount($updated->license),
                'seats_total' => $updated->license->max_activations,
            ]
        ));

        return $updated;
    }
}