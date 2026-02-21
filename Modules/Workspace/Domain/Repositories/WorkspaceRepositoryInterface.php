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
 *
 * Defines the contract for workspace data access operations.
 * Implements repository pattern for domain-driven design.
 * All methods work with domain entities, not persistence models.
 *
 * @see WorkspaceRepository For concrete implementation
 */
interface WorkspaceRepositoryInterface
{
    /**
     * Find workspace by its ID.
     *
     * @param  int  $id  The workspace ID
     * @return WorkspaceEntity|null The workspace entity or null if not found
     */
    public function findById(int $id): ?WorkspaceEntity;

    /**
     * Find workspace by its slug.
     *
     * @param  string  $slug  The workspace slug
     * @return WorkspaceEntity|null The workspace entity or null if not found
     */
    public function findBySlug(string $slug): ?WorkspaceEntity;

    /**
     * Find all workspaces owned by a specific user.
     *
     * @param  int  $ownerId  The owner user ID
     * @return array<int, WorkspaceEntity> Collection of workspace entities
     */
    public function findByOwnerId(int $ownerId): array;

    /**
     * Create a new workspace.
     *
     * @param  WorkspaceDTO  $workspaceDTO  The workspace data transfer object
     * @return WorkspaceEntity The created workspace entity
     */
    public function create(WorkspaceDTO $workspaceDTO): WorkspaceEntity;

    /**
     * Update an existing workspace.
     *
     * @param  int  $id  The workspace ID
     * @param  WorkspaceDTO  $workspaceDTO  The workspace data transfer object
     * @return WorkspaceEntity|null The updated workspace entity or null if not found
     */
    public function update(int $id, WorkspaceDTO $workspaceDTO): ?WorkspaceEntity;

    /**
     * Delete a workspace by ID.
     *
     * @param  int  $id  The workspace ID
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete(int $id): bool;

    /**
     * Get all workspaces.
     *
     * @return array<int, WorkspaceEntity> Collection of all workspace entities
     */
    public function getAll(): array;

    /**
     * Get all workspaces a user is a member of.
     *
     * @param  int  $userId  The user ID
     * @return array<int, WorkspaceEntity> Collection of workspace entities
     */
    public function getWorkspacesByUser(int $userId): array;

    /**
     * Add a user to a workspace with a specific role.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  int  $userId  The user ID to add
     * @param  string  $role  The role to assign (owner, admin, member)
     * @return bool True if added successfully, false otherwise
     */
    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool;

    /**
     * Remove a user from a workspace.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  int  $userId  The user ID to remove
     * @return int Number of affected rows (0 or 1)
     */
    public function removeUserFromWorkspace(int $workspaceId, int $userId): int;

    /**
     * Check if a workspace exists by ID.
     *
     * @param  int  $workspaceId  The workspace ID
     * @return bool True if workspace exists, false otherwise
     */
    public function workspaceExists(int $workspaceId): bool;

    /**
     * Find project by ID.
     *
     * @param  int  $id  The project ID
     * @return ProjectEntity|null The project entity or null if not found
     */
    public function findProjectById(int $id): ?ProjectEntity;

    /**
     * Create a new project.
     *
     * @param  ProjectDTO  $projectDTO  The project data transfer object
     * @return ProjectEntity The created project entity
     */
    public function createProject(ProjectDTO $projectDTO): ProjectEntity;

    /**
     * Check if user is a member of a workspace.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  int  $userId  The user ID
     * @return bool True if user is a member, false otherwise
     *
     * @throws \InvalidArgumentException If workspace does not exist
     */
    public function isUserMemberOfWorkspace(int $workspaceId, int $userId): bool;

    /**
     * Find task by ID.
     *
     * @param  int  $id  The task ID
     * @return TaskEntity|null The task entity or null if not found
     */
    public function findTaskById(int $id): ?TaskEntity;

    /**
     * Create a new task.
     *
     * @param  TaskDTO  $taskDTO  The task data transfer object
     * @return TaskEntity The created task entity
     */
    public function createTask(TaskDTO $taskDTO): TaskEntity;

    /**
     * Update a task.
     *
     * @param  int  $id  The task ID
     * @param  TaskDTO  $taskDTO  The task data transfer object
     * @return TaskEntity|null The updated task entity or null if not found
     */
    public function updateTask(int $id, TaskDTO $taskDTO): ?TaskEntity;

    /**
     * Check if user is a member of a project.
     *
     * @param  int  $projectId  The project ID
     * @param  int  $userId  The user ID
     * @return bool True if user is a member, false otherwise
     */
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
     * @param  int  $workspaceId  The workspace ID
     * @return array<int, ProjectEntity> Collection of project entities
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
     * @param  int  $taskId  The task ID
     * @param  int  $userId  The user ID requesting comments
     * @return array<int, TaskCommentEntity> Collection of comment entities
     */
    public function getCommentsByTask(int $taskId, int $userId): array;

    /**
     * Update an existing comment (only owner within 30 minutes).
     *
     * @param  int  $commentId  The comment ID
     * @param  string  $newComment  The new comment text
     * @param  int  $userId  The user ID
     * @return TaskCommentEntity The updated comment entity
     */
    public function updateComment(int $commentId, string $newComment, int $userId): TaskCommentEntity;

    /**
     * Get all attachments for a task.
     *
     * @param  int  $taskId  The task ID
     * @return array<int, TaskAttachmentEntity> Collection of attachment entities
     */
    public function getAttachmentsByTask(int $taskId): array;

    /**
     * Delete a task comment (only owner can delete).
     *
     * @param  int  $commentId  The comment ID
     * @param  int  $userId  The user ID
     * @return bool True if deleted successfully, false otherwise
     */
    public function deleteComment(int $commentId, int $userId): bool;

    /**
     * Delete a task attachment (only uploader can delete).
     *
     * @param  int  $attachmentId  The attachment ID
     * @param  int  $userId  The user ID
     * @return bool True if deleted successfully, false otherwise
     */
    public function deleteAttachment(int $attachmentId, int $userId): bool;

    /**
     * Update an existing project.
     *
     * @param  int  $id  The project ID
     * @param  ProjectDTO  $projectDTO  The project data transfer object
     * @return ProjectEntity|null The updated project entity or null if not found
     */
    public function updateProject(int $id, ProjectDTO $projectDTO): ?ProjectEntity;

    /**
     * Delete a project by ID.
     *
     * @param  int  $id  The project ID
     * @return bool True if deleted successfully, false otherwise
     */
    public function deleteProject(int $id): bool;

    /**
     * Get all tasks for a project.
     *
     * @param  int  $projectId  The project ID
     * @return array<int, TaskEntity> Collection of task entities
     */
    public function getTasksByProject(int $projectId): array;

    /**
     * Delete a task by ID.
     *
     * @param  int  $id  The task ID
     * @return bool True if deleted successfully, false otherwise
     */
    public function deleteTask(int $id): bool;

    /**
     * Get all members of a workspace.
     *
     * @param  int  $workspaceId  The workspace ID
     * @return array<int, array<string, mixed>> Collection of member data arrays
     */
    public function getWorkspaceMembers(int $workspaceId): array;
}
