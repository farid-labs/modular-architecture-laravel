<?php

namespace Modules\Workspace\Domain\Repositories;

use Modules\Workspace\Application\DTOs\ProjectDTO;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Entities\WorkspaceEntity;

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

    public function addCommentToTask(int $taskId, string $comment, int $userId): void;

    public function uploadAttachmentToTask(
        int $taskId,
        string $filePath,
        string $fileName,
        string $mimeType,
        int $fileSize,
        int $userId
    ): void;
}
