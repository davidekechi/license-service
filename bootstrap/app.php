<?php

use App\Modules\Shared\Middleware\AuthenticateBrand;
use App\Modules\Shared\Middleware\ValidateLicenseKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.brand' => AuthenticateBrand::class,
            'validate.license.key' => ValidateLicenseKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        \App\Modules\Brand\Providers\BrandServiceProvider::class,
        \App\Modules\License\Providers\LicenseServiceProvider::class,
        \App\Modules\AuditLog\Providers\AuditLogServiceProvider::class,
        \App\Modules\Shared\Providers\EventServiceProvider::class,
    ])
    ->create();
