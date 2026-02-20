<?php

namespace Modules\Workspace\Domain\Repositories;

use Modules\Workspace\Application\DTOs\ProjectDTO;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Entities\WorkspaceEntity;

/**
 * Workspace repository interface.
 * Defines the contract for workspace data access operations.
 */
interface WorkspaceRepositoryInterface
{
    // Find workspace by its ID
    public function findById(int $id): ?WorkspaceEntity;

    // Find workspace by its slug
    public function findBySlug(string $slug): ?WorkspaceEntity;

    /**
     * Find all workspaces owned by a specific user.
     *
     * @return array<int, WorkspaceEntity>
     */
    public function findByOwnerId(int $ownerId): array;

    // Create a new workspace
    public function create(WorkspaceDTO $workspaceDTO): WorkspaceEntity;

    // Update an existing workspace
    public function update(int $id, WorkspaceDTO $workspaceDTO): ?WorkspaceEntity;

    // Delete a workspace by ID
    public function delete(int $id): bool;

    /**
     * Get all workspaces
     *
     * @return array<int, WorkspaceEntity>
     */
    public function getAll(): array;

    /**
     * Get all workspaces a user is a member of
     *
     * @return array<int, WorkspaceEntity>
     */
    public function getWorkspacesByUser(int $userId): array;

    // Add a user to a workspace with a role
    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool;

    // Remove a user from a workspace, returns number of affected rows
    public function removeUserFromWorkspace(int $workspaceId, int $userId): int;

    /**
     * Check if a workspace exists by ID
     */
    public function workspaceExists(int $workspaceId): bool;

    // Find project by ID
    public function findProjectById(int $id): ?ProjectEntity;

    // Create a new project
    public function createProject(ProjectDTO $projectDTO): ProjectEntity;

    /**
     * Check if user is a member of a workspace
     *
     * @throws \InvalidArgumentException if workspace does not exist
     */
    public function isUserMemberOfWorkspace(int $workspaceId, int $userId): bool;

    // Find task by ID
    public function findTaskById(int $id): ?TaskEntity;

    // Create a new task
    public function createTask(TaskDTO $taskDTO): TaskEntity;

    // Update a task
    public function updateTask(int $id, TaskDTO $taskDTO): ?TaskEntity;

    // Check if user is a member of a project
    public function isUserMemberOfProject(int $projectId, int $userId): bool;

    /**
     * Add a comment to a task.
     *
     * @param  int  $taskId  The task ID
     * @param  string  $comment  The comment text
     * @param  int  $userId  The user ID
     * @return TaskCommentEntity The created comment entity
     */
    public function addCommentToTask(int $taskId, string $comment, int $userId): TaskCommentEntity;

    /**
     * Get all projects belonging to a workspace.
     *
     * @return array<int, ProjectEntity>
     */
    public function getProjectsByWorkspace(int $workspaceId): array;

    /**
     * Upload an attachment to a task.
     *
     * @param  int  $taskId  The task ID
     * @param  string  $filePath  The file path
     * @param  string  $fileName  The file name
     * @param  string  $mimeType  The MIME type
     * @param  int  $fileSize  The file size in bytes
     * @param  int  $userId  The user ID
     * @return TaskAttachmentEntity The created attachment entity
     */
    public function uploadAttachmentToTask(
        int $taskId,
        string $filePath,
        string $fileName,
        string $mimeType,
        int $fileSize,
        int $userId
    ): TaskAttachmentEntity;

    /**
     * Get all comments for a task.
     *
     * @return array<int, TaskCommentEntity>
     */
    public function getCommentsByTask(int $taskId, int $userId): array;

    /**
     * Update an existing comment (only owner within 30 minutes).
     */
    public function updateComment(int $commentId, string $newComment, int $userId): TaskCommentEntity;

    /**
     * Get all attachments for a task.
     *
     * @return array<int, TaskAttachmentEntity>
     */
    public function getAttachmentsByTask(int $taskId): array;

    public function deleteComment(int $commentId, int $userId): bool;

    public function deleteAttachment(int $attachmentId, int $userId): bool;
}
