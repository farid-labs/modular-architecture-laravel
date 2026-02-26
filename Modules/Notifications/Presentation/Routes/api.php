<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Presentation\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Notifications API Routes
|--------------------------------------------------------------------------
|
| Version: v1
| Middleware: auth:sanctum
| Module: Notifications
|
| Route naming convention:
| notifications.{action}
|
*/

Route::prefix('v1')
    ->middleware(['auth:sanctum'])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Query Endpoints
        |--------------------------------------------------------------------------
        */

        /**
         * List notifications with filters & pagination
         * GET /api/v1/notifications
         * Name: notifications.index
         */
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');

        /**
         * Get unread notifications count
         * GET /api/v1/notifications/unread-count
         * Name: notifications.unread-count
         */
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
            ->name('notifications.unread-count');

        /*
        |--------------------------------------------------------------------------
        | Command Endpoints
        |--------------------------------------------------------------------------
        */

        /**
         * Send a new notification
         * POST /api/v1/notifications/send
         * Name: notifications.send
         */
        Route::post('/notifications/send', [NotificationController::class, 'send'])
            ->name('notifications.send');

        /**
         * Mark specific notification as read
         * PATCH /api/v1/notifications/{id}/read
         * Name: notifications.mark-read
         */
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.mark-read');

        /**
         * Mark all notifications as read
         * POST /api/v1/notifications/mark-all-read
         * Name: notifications.mark-all-read
         */
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.mark-all-read');

        /**
         * Soft delete notification
         * DELETE /api/v1/notifications/{id}
         * Name: notifications.destroy
         */
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])
            ->name('notifications.destroy');
    });
