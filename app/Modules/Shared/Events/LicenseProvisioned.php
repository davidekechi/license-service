<?php

declare(strict_types=1);

namespace App\Modules\Shared\Events;

use App\Modules\License\Models\LicenseKey;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LicenseProvisioned
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly LicenseKey $licenseKey,
        public readonly string $brandPublicId,
        public readonly array $metadata = []
    ) {
    }

    /**
     * Get the auditable type.
     */
    public function getAuditableType(): string
    {
        return LicenseKey::class;
    }

    /**
     * Get the auditable ID.
     */
    public function getAuditableId(): string
    {
        return $this->licenseKey->public_id;
    }

    /**
     * Get the event type.
     */
    public function getEventType(): string
    {
        return 'license_provisioned';
    }

    /**
     * Get the actor type.
     */
    public function getActorType(): string
    {
        return 'brand';
    }

    /**
     * Get the actor identifier.
     */
    public function getActorIdentifier(): string
    {
        return $this->brandPublicId;
    }

    /**
     * Get metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        Log::info('event raised');

        return \array_merge($this->metadata, [
            'license_count'  => $this->licenseKey->licenses()->count(),
            'customer_email' => $this->licenseKey->customer_email,
        ]);
    }
}
