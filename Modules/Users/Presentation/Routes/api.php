<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Presentation\Controllers\AuthController;
use Modules\Users\Presentation\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Users Module API Routes
|--------------------------------------------------------------------------
|
| Version: v1
| Module: Users
|
| Naming Convention:
| users.{section}.{action}
|
| Sections:
| - auth      → Authentication (public)
| - profile   → Authenticated user's self-management
| - admin     → Administrative user management
|
*/

Route::prefix('v1')
    ->name('users.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Authentication Routes (Public)
        |--------------------------------------------------------------------------
        | Rate limited to prevent brute force attacks.
        | Middleware: throttle:api-auth
        |
        */

        Route::prefix('auth')
            ->middleware('throttle:api-auth')
            ->name('auth.')
            ->group(function () {

                /**
                 * Register a new user account
                 * POST /api/v1/auth/register
                 * Name: users.auth.register
                 */
                Route::post('/register', [AuthController::class, 'register'])
                    ->name('register');

                /**
                 * Login and receive access token
                 * POST /api/v1/auth/login
                 * Name: users.auth.login
                 */
                Route::post('/login', [AuthController::class, 'login'])
                    ->name('login');

                /**
                 * Send password reset link
                 * POST /api/v1/auth/password/forgot
                 * Name: users.auth.password.email
                 */
                Route::post('/password/forgot', [AuthController::class, 'sendResetLink'])
                    ->name('password.email');

                /**
                 * Reset password using token
                 * POST /api/v1/auth/password/reset
                 * Name: users.auth.password.update
                 */
                Route::post('/password/reset', [AuthController::class, 'resetPassword'])
                    ->name('password.update');
            });

        /*
        |--------------------------------------------------------------------------
        | Protected Routes (Authenticated Users)
        |--------------------------------------------------------------------------
        | Middleware:
        | - auth:sanctum
        | - throttle:api-users
        |
        */

        Route::middleware(['auth:sanctum', 'throttle:api-users'])
            ->group(function () {

                /*
                |--------------------------------------------------------------------------
                | Self Profile Management
                |--------------------------------------------------------------------------
                | Endpoints for the authenticated user to manage their own account.
                |
                */

                Route::prefix('user')
                    ->name('profile.')
                    ->group(function () {

                        /**
                         * Get authenticated user's profile
                         * GET /api/v1/user/me
                         * Name: users.profile.show
                         */
                        Route::get('/me', [AuthController::class, 'me'])
                            ->name('show');

                        /**
                         * Logout current user (invalidate token)
                         * POST /api/v1/user/logout
                         * Name: users.profile.logout
                         */
                        Route::post('/logout', [AuthController::class, 'logout'])
                            ->name('logout');

                        /**
                         * Update authenticated user's profile
                         * PUT /api/v1/user/me
                         * Name: users.profile.update
                         */
                        Route::put('/me', [UserController::class, 'updateProfile'])
                            ->name('update');

                        /**
                         * Delete authenticated user's account
                         * DELETE /api/v1/user/me
                         * Name: users.profile.delete
                         */
                        Route::delete('/me', [UserController::class, 'destroyProfile'])
                            ->name('delete');
                    });

                /*
                |--------------------------------------------------------------------------
                | Administrative User Management (CRUD)
                |--------------------------------------------------------------------------
                | Intended for admin usage.
                | Route model binding uses {user}
                |
                */

                Route::apiResource('users', UserController::class)
                    ->only(['index', 'show', 'store', 'update', 'destroy'])
                    ->parameters(['users' => 'user'])
                    ->whereNumber('user')
                    ->names([
                        'index' => 'admin.index',
                        'store' => 'admin.store',
                        'show' => 'admin.show',
                        'update' => 'admin.update',
                        'destroy' => 'admin.destroy',
                    ]);
            });
    });
