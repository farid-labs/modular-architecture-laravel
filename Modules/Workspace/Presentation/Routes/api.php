<?php

use Illuminate\Support\Facades\Route;
use Modules\Workspace\Presentation\Controllers\ProjectController;
use Modules\Workspace\Presentation\Controllers\TaskAttachmentController;
use Modules\Workspace\Presentation\Controllers\TaskCommentController;
use Modules\Workspace\Presentation\Controllers\TaskController;
use Modules\Workspace\Presentation\Controllers\WorkspaceController;

/**
 * Workspace module API routes.
 *
 * All routes are prefixed with /api/v1 and require Sanctum authentication.
 * Routes are organized by resource type for clarity and maintainability.
 *
 * @see WorkspaceController For workspace CRUD operations
 * @see ProjectController For project management
 * @see TaskController For task operations
 * @see TaskCommentController For comment management
 * @see TaskAttachmentController For file attachment handling
 */

// API v1 routes with Sanctum authentication middleware
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    // ===== Workspaces =====
    // Base workspace resource routes (index, store, show)
    // Note: show uses slug parameter, update/destroy use numeric ID
    Route::apiResource('workspaces', WorkspaceController::class)
        ->only(['index', 'store', 'show']);

    // Update workspace by numeric ID only (owner authorization required)
    Route::put('/workspaces/{id}', [WorkspaceController::class, 'update'])
        ->whereNumber('id')
        ->name('workspaces.update');

    // Delete workspace by numeric ID only (owner authorization required)
    Route::delete('/workspaces/{id}', [WorkspaceController::class, 'destroy'])
        ->whereNumber('id')
        ->name('workspaces.destroy');

    // ===== Workspace Members =====
    // Add a member to a workspace (owner/admin only)
    Route::post('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'addMember'])
        ->whereNumber('workspaceId')
        ->name('workspaces.members.add');

    // Remove a member from a workspace (owner/admin only)
    Route::delete('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'removeMember'])
        ->whereNumber('workspaceId')
        ->name('workspaces.members.remove');

    // List all workspace members (members only)
    Route::get('/workspaces/{workspaceId}/members', [WorkspaceController::class, 'indexMembers'])
        ->whereNumber('workspaceId')
        ->name('workspaces.members.index');

    // ===== Projects (Nested under Workspaces) =====
    // List all projects within a workspace
    Route::get('/workspaces/{workspaceId}/projects', [ProjectController::class, 'index'])
        ->whereNumber('workspaceId')
        ->name('projects.index');

    // Create a new project within a workspace
    Route::post('/workspaces/{workspaceId}/projects', [ProjectController::class, 'store'])
        ->whereNumber('workspaceId')
        ->name('projects.create');

    // Get a single project by numeric ID
    Route::get('/projects/{id}', [ProjectController::class, 'show'])
        ->whereNumber('id')
        ->name('projects.show');

    // Update project by numeric ID
    Route::put('/projects/{id}', [ProjectController::class, 'update'])
        ->whereNumber('id')
        ->name('projects.update');

    // Delete project by numeric ID
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])
        ->whereNumber('id')
        ->name('projects.destroy');

    // ===== Tasks =====
    // List all tasks within a project
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'index'])
        ->whereNumber('projectId')
        ->name('tasks.index');

    // Create a new task within a project
    Route::post('/projects/{projectId}/tasks', [TaskController::class, 'store'])
        ->whereNumber('projectId')
        ->name('tasks.store');

    // Get a single task by numeric ID
    Route::get('/tasks/{id}', [TaskController::class, 'show'])
        ->whereNumber('id')
        ->name('tasks.show');

    // Mark a task as completed
    Route::put('/tasks/{id}/complete', [TaskController::class, 'complete'])
        ->whereNumber('id')
        ->name('tasks.complete');

    // Update task by numeric ID
    Route::put('/tasks/{id}', [TaskController::class, 'update'])
        ->whereNumber('id')
        ->name('tasks.update');

    // Delete task by numeric ID
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])
        ->whereNumber('id')
        ->name('tasks.destroy');

    // ===== Task Comments =====
    // List all comments for a task
    Route::get('/tasks/{taskId}/comments', [TaskCommentController::class, 'index'])
        ->whereNumber('taskId')
        ->name('tasks.comments.index');

    // Add a new comment to a task
    Route::post('/tasks/{taskId}/comments', [TaskCommentController::class, 'store'])
        ->whereNumber('taskId')
        ->name('tasks.comments.store');

    // Update comment by numeric ID (author only, within 30 minutes)
    Route::put('/comments/{commentId}', [TaskCommentController::class, 'update'])
        ->whereNumber('commentId')
        ->name('comments.update');

    // Delete comment by numeric ID (author only)
    Route::delete('/tasks/{taskId}/comments/{commentId}', [TaskCommentController::class, 'destroy'])
        ->whereNumber(['taskId', 'commentId'])
        ->name('tasks.comments.destroy');

    // ===== Task Attachments =====
    // List all attachments for a task
    Route::get('/tasks/{taskId}/attachments', [TaskAttachmentController::class, 'index'])
        ->whereNumber('taskId')
        ->name('tasks.attachments.index');

    // Upload a new attachment to a task
    Route::post('/tasks/{taskId}/attachments', [TaskAttachmentController::class, 'store'])
        ->whereNumber('taskId')
        ->name('tasks.attachments.store');

    // Delete attachment by numeric ID (uploader only)
    Route::delete('/tasks/{taskId}/attachments/{attachmentId}', [TaskAttachmentController::class, 'destroy'])
        ->whereNumber(['taskId', 'attachmentId'])
        ->name('tasks.attachments.destroy');
});
