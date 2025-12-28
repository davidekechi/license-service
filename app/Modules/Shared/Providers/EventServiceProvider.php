<?php

declare(strict_types=1);

namespace App\Modules\Shared\Providers;

use App\Modules\AuditLog\Listeners\CreateAuditLogListener;
use App\Modules\Shared\Events\LicenseActivated;
use App\Modules\Shared\Events\LicenseProvisioned;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        LicenseProvisioned::class => [
            CreateAuditLogListener::class,
        ],
        LicenseActivated::class => [
            CreateAuditLogListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
