<?php

declare(strict_types=1);

namespace App\Modules\Shared\Contracts;

interface AuditableEvent
{
    /**
     * Get the auditable type.
     */
    public function getAuditableType(): string;

    /**
     * Get the auditable ID.
     */
    public function getAuditableId(): string;

    /**
     * Get the event type.
     */
    public function getEventType(): string;

    /**
     * Get the actor type.
     */
    public function getActorType(): string;

    /**
     * Get the actor identifier.
     */
    public function getActorIdentifier(): string;

    /**
     * Get metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
