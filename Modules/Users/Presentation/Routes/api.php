<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Presentation\Controllers\AuthController;
use Modules\Users\Presentation\Controllers\UserController;

Route::prefix('v1')->group(function () {

    // Public routes with stricter rate limiting
    Route::middleware(['throttle:api-auth'])->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
    });
    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Auth routes
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // User routes with specific rate limiting
        Route::middleware(['throttle:api-users'])->group(function () {
            Route::apiResource('users', UserController::class);
        });
    });
});
