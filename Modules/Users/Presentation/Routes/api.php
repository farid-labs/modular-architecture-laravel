<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Presentation\Controllers\AuthController;
use Modules\Users\Presentation\Controllers\UserController;

Route::prefix('v1')->group(function () {

    Route::middleware('throttle:api-auth')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
    });

    Route::middleware(['auth:sanctum', 'throttle:api-users'])->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::apiResource('users', UserController::class);
    });
});
