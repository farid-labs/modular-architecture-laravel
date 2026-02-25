<?php
// Presentation/Routes/api.php

use Illuminate\Support\Facades\Route;
use Modules\Users\Presentation\Controllers\AuthController;
use Modules\Users\Presentation\Controllers\UserController;

Route::prefix('v1')->name('api.v1.')->group(function () {

    // --- Public Auth Routes (Rate Limited) ---
    Route::prefix('auth')->middleware('throttle:api-auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

        // Password Reset Flow
        Route::post('/password/forgot', [AuthController::class, 'sendResetLink'])->name('password.email');
        Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
    });

    // --- Protected Routes (Auth Required) ---
    Route::middleware(['auth:sanctum', 'throttle:api-users'])->group(function () {

        // User Profile (Self-Management)
        Route::prefix('user')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('user.profile');
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::put('/me', [UserController::class, 'updateProfile'])->name('user.update'); // Separate from admin update
            Route::delete('/me', [UserController::class, 'destroyProfile'])->name('user.delete');
        });

        // User Management (Admin/CRUD)
        Route::apiResource('users', UserController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy'])
            ->parameters(['users' => 'user']) // Ensure route model binding uses 'user'
            ->whereNumber('user');
    });
});
