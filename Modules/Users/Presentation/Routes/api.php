<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Presentation\Controllers\UserController;
use Modules\Users\Presentation\Controllers\AuthController;

Route::prefix('v1')->group(function () {
    
    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
    });
});