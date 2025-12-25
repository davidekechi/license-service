<?php

declare(strict_types=1);

namespace App\Modules\Brand\Providers;

use App\Modules\Brand\Contracts\BrandLookupServiceInterface;
use App\Modules\Brand\Contracts\BrandRepositoryInterface;
use App\Modules\Brand\Contracts\ProductRepositoryInterface;
use App\Modules\Brand\Repositories\BrandRepository;
use App\Modules\Brand\Repositories\ProductRepository;
use App\Modules\Brand\Services\BrandLookupService;
use Illuminate\Support\ServiceProvider;

class BrandServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(BrandRepositoryInterface::class, BrandRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);

        // Register lookup service for cross-module access
        $this->app->bind(BrandLookupServiceInterface::class, BrandLookupService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
