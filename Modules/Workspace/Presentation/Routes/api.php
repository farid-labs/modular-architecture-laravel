<?php

use Illuminate\Support\Facades\Route;
use Modules\Workspace\Presentation\Controllers\ProjectController;
use Modules\Workspace\Presentation\Controllers\TaskController;
use Modules\Workspace\Presentation\Controllers\WorkspaceController;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    // ===== Workspaces =====
    Route::apiResource('workspaces', WorkspaceController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::post('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'addMember'])
        ->whereNumber('workspaceId');
    Route::delete('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'removeMember'])
        ->whereNumber('workspaceId');

    // ===== Projects (Nested under Workspaces) =====
    Route::get('/workspaces/{workspaceId}/projects', [ProjectController::class, 'index'])
        ->whereNumber('workspaceId');
    Route::post('/workspaces/{workspaceId}/projects', [ProjectController::class, 'store'])
        ->whereNumber('workspaceId');
    Route::get('/projects/{id}', [ProjectController::class, 'show'])
        ->whereNumber('id');

    // ===== Tasks (Nested under Projects) =====
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'index'])
        ->whereNumber('projectId');
    Route::post('/projects/{projectId}/tasks', [TaskController::class, 'store'])
        ->whereNumber('projectId');
    Route::get('/tasks/{id}', [TaskController::class, 'show'])
        ->whereNumber('id');
    Route::put('/tasks/{id}/complete', [TaskController::class, 'complete'])
        ->whereNumber('id');
});
