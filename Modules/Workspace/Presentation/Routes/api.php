<?php

use Illuminate\Support\Facades\Route;
use Modules\Workspace\Presentation\Controllers\ProjectController;
use Modules\Workspace\Presentation\Controllers\TaskController;
use Modules\Workspace\Presentation\Controllers\WorkspaceController;

// API v1 routes with Sanctum authentication
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    // ===== Workspaces =====
    // Base workspace routes (show uses slug; no numeric constraint)
    Route::apiResource('workspaces', WorkspaceController::class)
        ->only(['index', 'store', 'show']);

    // Update workspace by numeric ID only
    Route::put('/workspaces/{id}', [WorkspaceController::class, 'update'])
        ->whereNumber('id')
        ->name('workspaces.update');

    // Delete workspace by numeric ID only
    Route::delete('/workspaces/{id}', [WorkspaceController::class, 'destroy'])
        ->whereNumber('id')
        ->name('workspaces.destroy');

    // ===== Workspace members =====
    // Add a member to a workspace
    Route::post('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'addMember'])
        ->whereNumber('workspaceId')
        ->name('workspaces.members.add');

    // Remove a member from a workspace
    Route::delete('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'removeMember'])
        ->whereNumber('workspaceId')
        ->name('workspaces.members.remove');

    // ===== Projects (Nested under Workspaces) =====
    // List projects within a workspace
    Route::get('/workspaces/{workspaceId}/projects', [ProjectController::class, 'index'])
        ->whereNumber('workspaceId');

    // Create a new project within a workspace
    Route::post('/workspaces/{workspaceId}/projects', [ProjectController::class, 'store'])
        ->whereNumber('workspaceId');

    // Get a single project by numeric ID
    Route::get('/projects/{id}', [ProjectController::class, 'show'])
        ->whereNumber('id');

    // ===== Tasks (Nested under Projects) =====
    // List tasks within a project
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'index'])
        ->whereNumber('projectId');

    // Create a new task within a project
    Route::post('/projects/{projectId}/tasks', [TaskController::class, 'store'])
        ->whereNumber('projectId');

    // Get a single task by numeric ID
    Route::get('/tasks/{id}', [TaskController::class, 'show'])
        ->whereNumber('id');

    // Mark a task as completed
    Route::put('/tasks/{id}/complete', [TaskController::class, 'complete'])
        ->whereNumber('id');
});
