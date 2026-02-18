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
    public function findById(int $id): ?WorkspaceEntity;

    public function findBySlug(string $slug): ?WorkspaceEntity;

    /**
     * @return array<int, WorkspaceEntity>
     */
    public function findByOwnerId(int $ownerId): array;

    public function create(WorkspaceDTO $workspaceDTO): WorkspaceEntity;

    public function update(int $id, WorkspaceDTO $workspaceDTO): ?WorkspaceEntity;

    public function delete(int $id): bool;

    /**
     * @return array<int, WorkspaceEntity>
     */
    public function getAll(): array;

    /**
     * @return array<int, WorkspaceEntity>
     */
    public function getWorkspacesByUser(int $userId): array;

    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool;

    public function removeUserFromWorkspace(int $workspaceId, int $userId): bool;

    public function findProjectById(int $id): ?ProjectEntity;

    public function createProject(ProjectDTO $projectDTO): ProjectEntity;

    public function isUserMemberOfWorkspace(int $workspaceId, int $userId): bool;

    public function findTaskById(int $id): ?TaskEntity;

    public function createTask(TaskDTO $taskDTO): TaskEntity;

    public function updateTask(int $id, TaskDTO $taskDTO): ?TaskEntity;

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
}
