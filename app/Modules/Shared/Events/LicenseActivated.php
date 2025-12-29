<?php

declare(strict_types=1);

namespace App\Modules\Shared\Events;

use App\Modules\License\Models\LicenseActivation;
use App\Modules\Shared\Contracts\AuditableEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicenseActivated implements AuditableEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly LicenseActivation $activation,
        public readonly string $licenseKeyString,
        public readonly array $metadata = []
    ) {
    }

    /**
     * Get the auditable type.
     */
    public function getAuditableType(): string
    {
        return LicenseActivation::class;
    }

    /**
     * Get the auditable ID.
     */
    public function getAuditableId(): string
    {
        return $this->activation->public_id;
    }

    /**
     * Get the event type.
     */
    public function getEventType(): string
    {
        return 'license_activated';
    }

    /**
     * Get the actor type.
     */
    public function getActorType(): string
    {
        return 'product';
    }

    /**
     * Get the actor identifier.
     */
    public function getActorIdentifier(): string
    {
        return $this->activation->license->product_id;
    }

    /**
     * Get metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return \array_merge($this->metadata, [
            'license_key'         => $this->licenseKeyString,
            'instance_identifier' => $this->activation->instance_identifier,
            'instance_type'       => $this->activation->instance_type->value,
            'license_id'          => $this->activation->license->public_id,
        ]);
    }
}
