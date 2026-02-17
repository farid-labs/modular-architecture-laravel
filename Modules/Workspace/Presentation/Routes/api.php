<?php

use Illuminate\Support\Facades\Route;
use Modules\Workspace\Presentation\Controllers\WorkspaceController;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/workspaces', [WorkspaceController::class, 'index']);
        Route::get('/workspaces/{slug}', [WorkspaceController::class, 'show']);
        Route::post('/workspaces', [WorkspaceController::class, 'store']);
        Route::put('/workspaces/{id}', [WorkspaceController::class, 'update'])
            ->whereNumber('id');
        Route::delete('/workspaces/{id}', [WorkspaceController::class, 'destroy'])
            ->whereNumber('id');

        // Members
        Route::post('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'addMember'])
            ->whereNumber('workspaceId');
        Route::delete('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'removeMember'])
            ->whereNumber('workspaceId');
    });
});
