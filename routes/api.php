<?php

declare(strict_types=1);

use App\Modules\License\Controllers\CustomerLicenseController;
use App\Modules\License\Controllers\LicenseActivationController;
use App\Modules\License\Controllers\LicenseProvisioningController;
use App\Modules\License\Controllers\LicenseStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status'      => 'healthy',
            'message'     => 'License Service API is running',
            'timestamp'   => now()->toISOString(),
            'version'     => '1.0.0',
            'environment' => app()->environment()
        ]);
    });

    Route::prefix('brands')->middleware(['auth.brand'])->group(function () {
        // US1: Provision License
        Route::post('/licenses/provision', [LicenseProvisioningController::class, 'provision'])
            ->name('licenses.provision');

        // US6: List licenses by customer email
        Route::get('/customers/{email}/licenses', [CustomerLicenseController::class, 'listByEmail'])
            ->name('customers.licenses');
    });

    // Public routes (no brand auth required for activation)
    Route::prefix('licenses')->group(function () {
        // US3: Activate License
        Route::post('/{licenseKey}/activate', [LicenseActivationController::class, 'activate'])
            ->name('licenses.activate');

        // US4: Check License Status
        Route::get('/{licenseKey}/status', [LicenseStatusController::class, 'status'])
            ->name('licenses.status');
    });
});
