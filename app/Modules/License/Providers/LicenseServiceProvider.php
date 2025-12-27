<?php

declare(strict_types=1);

namespace App\Modules\License\Providers;

use App\Modules\License\Contracts\LicenseActivationRepositoryInterface;
use App\Modules\License\Contracts\LicenseKeyRepositoryInterface;
use App\Modules\License\Contracts\LicenseLookupServiceInterface;
use App\Modules\License\Contracts\LicenseRepositoryInterface;
use App\Modules\License\Repositories\LicenseActivationRepository;
use App\Modules\License\Repositories\LicenseKeyRepository;
use App\Modules\License\Repositories\LicenseRepository;
use App\Modules\License\Services\LicenseLookupService;
use Illuminate\Support\ServiceProvider;

class LicenseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(LicenseKeyRepositoryInterface::class, LicenseKeyRepository::class);
        $this->app->bind(LicenseRepositoryInterface::class, LicenseRepository::class);
        $this->app->bind(LicenseActivationRepositoryInterface::class, LicenseActivationRepository::class);

        // Register lookup service for cross-module access
        $this->app->bind(LicenseLookupServiceInterface::class, LicenseLookupService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
