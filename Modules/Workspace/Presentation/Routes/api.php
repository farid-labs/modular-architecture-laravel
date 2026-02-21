<?php

use Illuminate\Support\Facades\Route;
use Modules\Workspace\Presentation\Controllers\ProjectController;
use Modules\Workspace\Presentation\Controllers\TaskAttachmentController;
use Modules\Workspace\Presentation\Controllers\TaskCommentController;
use Modules\Workspace\Presentation\Controllers\TaskController;
use Modules\Workspace\Presentation\Controllers\WorkspaceController;

// API v1 routes with Sanctum authentication
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    // ===== Workspaces =====
    Route::apiResource('workspaces', WorkspaceController::class)
        ->only(['index', 'store', 'show']);

    Route::put('/workspaces/{id}', [WorkspaceController::class, 'update'])
        ->whereNumber('id')
        ->name('workspaces.update');

    Route::delete('/workspaces/{id}', [WorkspaceController::class, 'destroy'])
        ->whereNumber('id')
        ->name('workspaces.destroy');

    // ===== Workspace members =====
    Route::post('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'addMember'])
        ->whereNumber('workspaceId')
        ->name('workspaces.members.add');

    Route::delete('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'removeMember'])
        ->whereNumber('workspaceId')
        ->name('workspaces.members.remove');

    Route::get('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'indexMembers'])
        ->whereNumber('workspaceId')
        ->name('workspaces.members.index');

    // ===== Projects (Nested under Workspaces) =====
    Route::get('/workspaces/{workspaceId}/projects', [ProjectController::class, 'index'])
        ->whereNumber('workspaceId')
        ->name('projects.index');

    Route::post('/workspaces/{workspaceId}/projects', [ProjectController::class, 'store'])
        ->whereNumber('workspaceId')
        ->name('projects.create');

    Route::get('/projects/{id}', [ProjectController::class, 'show'])
        ->whereNumber('id')
        ->name('projects.show');

    Route::put('/projects/{id}', [ProjectController::class, 'update'])
        ->whereNumber('id')
        ->name('projects.update');

    Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])
        ->whereNumber('id')
        ->name('projects.destroy');

    // ===== Tasks =====
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'index'])
        ->whereNumber('projectId')
        ->name('tasks.index');

    Route::post('/projects/{projectId}/tasks', [TaskController::class, 'store'])
        ->whereNumber('projectId')
        ->name('tasks.store');

    Route::get('/tasks/{id}', [TaskController::class, 'show'])
        ->whereNumber('id')
        ->name('tasks.show');

    Route::put('/tasks/{id}/complete', [TaskController::class, 'complete'])
        ->whereNumber('id')
        ->name('tasks.complete');

    Route::put('/tasks/{id}', [TaskController::class, 'update'])
        ->whereNumber('id')
        ->name('tasks.update');

    Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])
        ->whereNumber('id')
        ->name('tasks.destroy');

    // ===== Task Comments =====
    Route::get('/tasks/{taskId}/comments', [TaskCommentController::class, 'index'])
        ->whereNumber('taskId')
        ->name('tasks.comments.index');

    Route::post('/tasks/{taskId}/comments', [TaskCommentController::class, 'store'])
        ->whereNumber('taskId')
        ->name('tasks.comments.store');

    Route::put('/comments/{commentId}', [TaskCommentController::class, 'update'])
        ->whereNumber('commentId')
        ->name('comments.update');

    Route::delete('/tasks/{taskId}/comments/{commentId}', [TaskCommentController::class, 'destroy'])
        ->whereNumber(['taskId', 'commentId'])
        ->name('tasks.comments.destroy');

    // ===== Task Attachments =====
    Route::get('/tasks/{taskId}/attachments', [TaskAttachmentController::class, 'index'])
        ->whereNumber('taskId')
        ->name('tasks.attachments.index');

    Route::post('/tasks/{taskId}/attachments', [TaskAttachmentController::class, 'store'])
        ->whereNumber('taskId')
        ->name('tasks.attachments.store');

    Route::delete('/tasks/{taskId}/attachments/{attachmentId}', [TaskAttachmentController::class, 'destroy'])
        ->whereNumber(['taskId', 'attachmentId'])
        ->name('tasks.attachments.destroy');
});
