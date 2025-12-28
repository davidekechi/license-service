<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Listeners;

use App\Modules\AuditLog\Contracts\AuditLogRepositoryInterface;
use App\Modules\Shared\Contracts\AuditableEvent;
use Illuminate\Support\Facades\Log;

class CreateAuditLogListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(AuditableEvent $event): void
    {
        try {
            $this->auditLogRepository->create([
                'auditable_type'   => $event->getAuditableType(),
                'auditable_id'     => $event->getAuditableId(),
                'event'            => $event->getEventType(),
                'actor_type'       => $event->getActorType(),
                'actor_identifier' => $event->getActorIdentifier(),
                'metadata'         => $event->getMetadata(),
                'ip_address'       => request()->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log', [
                'event' => \get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
