<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

// Health check endpoints
Route::get('/health', [HealthController::class, 'index'])->name('health');

// Metrics endpoint (consider adding authentication in production)
Route::get('/metrics', [MetricsController::class, 'index'])->name('metrics');
