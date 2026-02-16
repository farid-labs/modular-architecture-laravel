<?php

use Illuminate\Support\Facades\Route;
use Modules\Workspace\Presentation\Controllers\WorkspaceController;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('workspaces', WorkspaceController::class)
        ->whereNumber('workspace');

    Route::post('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'addMember'])
        ->whereNumber('workspaceId');

    Route::delete('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'removeMember'])
        ->whereNumber('workspaceId');
});
