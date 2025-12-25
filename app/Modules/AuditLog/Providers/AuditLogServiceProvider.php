<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Providers;

use App\Modules\AuditLog\Contracts\AuditLogRepositoryInterface;
use App\Modules\AuditLog\Repositories\AuditLogRepository;
use Illuminate\Support\ServiceProvider;

class AuditLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
