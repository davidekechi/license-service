<?php

declare(strict_types=1);

use App\Modules\License\Controllers\LicenseProvisioningController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| License API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/brands/licenses')->middleware(['auth.brand'])->group(function () {
    // US1: Provision License
    Route::post('/provision', [LicenseProvisioningController::class, 'provision'])
        ->name('licenses.provision');
});
