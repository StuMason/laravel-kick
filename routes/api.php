<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use StuMason\Kick\Http\Controllers\ArtisanController;
use StuMason\Kick\Http\Controllers\HealthController;
use StuMason\Kick\Http\Controllers\LogController;
use StuMason\Kick\Http\Controllers\QueueController;
use StuMason\Kick\Http\Controllers\StatsController;
use StuMason\Kick\Kick;

Route::prefix(Kick::prefix())->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Health Endpoint
    |--------------------------------------------------------------------------
    */
    Route::middleware(['kick.auth:health:read'])
        ->get('/health', HealthController::class);

    /*
    |--------------------------------------------------------------------------
    | Stats Endpoint
    |--------------------------------------------------------------------------
    */
    Route::middleware(['kick.auth:stats:read'])
        ->get('/stats', StatsController::class);

    /*
    |--------------------------------------------------------------------------
    | Log Endpoints
    |--------------------------------------------------------------------------
    */
    Route::middleware(['kick.auth:logs:read'])->group(function () {
        Route::get('/logs', [LogController::class, 'index']);
        Route::get('/logs/{file}', [LogController::class, 'show']);

        Route::post('/logs/test', function () {
            $levels = ['debug', 'info', 'notice', 'warning', 'error'];
            foreach ($levels as $level) {
                Log::$level("[Kick Test] This is a {$level} message at ".now()->toDateTimeString());
            }

            return response()->json([
                'message' => 'Test log entries written',
                'levels' => $levels,
                'timestamp' => now()->toDateTimeString(),
            ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Queue Endpoints
    |--------------------------------------------------------------------------
    */
    Route::middleware(['kick.auth:queue:read'])->group(function () {
        Route::get('/queue', [QueueController::class, 'index']);
        Route::get('/queue/failed', [QueueController::class, 'failed']);
    });

    Route::middleware(['kick.auth:queue:retry'])->group(function () {
        Route::post('/queue/retry/{id}', [QueueController::class, 'retry']);
        Route::post('/queue/retry-all', [QueueController::class, 'retryAll']);
    });

    /*
    |--------------------------------------------------------------------------
    | Artisan Endpoints
    |--------------------------------------------------------------------------
    */
    Route::middleware(['kick.auth:artisan:list'])
        ->get('/artisan', [ArtisanController::class, 'index']);

    Route::middleware(['kick.auth:artisan:execute'])
        ->post('/artisan', [ArtisanController::class, 'execute']);
});
