<?php

declare(strict_types=1);

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
            'message'     => 'Skills Guide API is running',
            'timestamp'   => now()->toISOString(),
            'version'     => '1.0.0',
            'environment' => app()->environment()
        ]);
    });
});
