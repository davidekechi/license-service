<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

// Health check endpoints
Route::get('/health', [HealthController::class, 'index'])->name('health');