<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Brand\Providers\BrandServiceProvider::class,
    App\Modules\License\Providers\LicenseServiceProvider::class,
    App\Modules\AuditLog\Providers\AuditLogServiceProvider::class,
    App\Modules\Shared\Providers\EventServiceProvider::class,
];
