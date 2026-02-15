<?php

namespace Modules\Workspace\Application\Services;

use Illuminate\Support\Facades\Log;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Application\DTOs\ProjectDTO;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Entities\WorkspaceEntity;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class WorkspaceService
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository
    ) {}

    /**
     * @return array<int, WorkspaceEntity>
     */
    public function getWorkspacesByUser(int $userId): array
    {
        Log::channel('domain')->debug('Fetching workspaces for user', ['user_id' => $userId]);

        return $this->workspaceRepository->getWorkspacesByUser($userId);
    }

    public function getWorkspaceBySlug(string $slug): WorkspaceEntity
    {
        Log::channel('domain')->debug('Fetching workspace by slug', ['slug' => $slug]);
        $workspace = $this->workspaceRepository->findBySlug($slug);
        if (! $workspace) {
            throw new \InvalidArgumentException("Workspace with slug '{$slug}' not found");
        }

        return $workspace;
    }

    public function createWorkspace(WorkspaceDTO $workspaceDTO, UserModel $user): WorkspaceEntity
    {
        Log::channel('domain')->info('Creating workspace', [
            'name' => $workspaceDTO->name,
            'owner_id' => $user->id,
        ]);

        $data = $workspaceDTO->toArray();
        $data['owner_id'] = $user->id;
        $workspaceDTO = WorkspaceDTO::fromArray($data);

        $workspace = $this->workspaceRepository->create($workspaceDTO);

        Log::channel('domain')->info('Workspace created successfully', [
            'workspace_id' => $workspace->getId(),
            'owner_id' => $user->id,
        ]);

        return $workspace;
    }

    public function updateWorkspace(int $id, WorkspaceDTO $workspaceDTO): WorkspaceEntity
    {
        Log::channel('domain')->info('Updating workspace', ['workspace_id' => $id]);

        $workspace = $this->workspaceRepository->update($id, $workspaceDTO);
        if (! $workspace) {
            throw new \InvalidArgumentException("Workspace with ID {$id} not found");
        }

        Log::channel('domain')->info('Workspace updated successfully', ['workspace_id' => $id]);

        return $workspace;
    }

    public function deleteWorkspace(int $id): bool
    {
        Log::channel('domain')->info('Deleting workspace', ['workspace_id' => $id]);

        $result = $this->workspaceRepository->delete($id);

        if ($result) {
            Log::channel('domain')->info('Workspace deleted successfully', ['workspace_id' => $id]);
        } else {
            Log::channel('domain')->warning('Workspace not found for deletion', ['workspace_id' => $id]);
        }

        return $result;
    }

    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool
    {
        Log::channel('domain')->info('Adding user to workspace', [
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'role' => $role,
        ]);

        $validRoles = ['owner', 'admin', 'member'];
        if (! in_array($role, $validRoles)) {
            throw new \InvalidArgumentException("Invalid role '{$role}'. Must be one of: ".implode(', ', $validRoles));
        }

        $result = $this->workspaceRepository->addUserToWorkspace($workspaceId, $userId, $role);

        if ($result) {
            Log::channel('domain')->info('User added to workspace successfully', [
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
            ]);
        }

        return $result;
    }

    public function removeUserFromWorkspace(int $workspaceId, int $userId): bool
    {
        Log::channel('domain')->info('Removing user from workspace', [
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
        ]);

        $result = $this->workspaceRepository->removeUserFromWorkspace($workspaceId, $userId);

        if ($result) {
            Log::channel('domain')->info('User removed from workspace successfully', [
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
            ]);
        } else {
            Log::channel('domain')->warning('User not found in workspace for removal', [
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
            ]);
        }

        return $result;
    }

    public function createProject(ProjectDTO $projectDTO, UserModel $user): ProjectEntity
    {
        Log::channel('domain')->info('Creating project', [
            'name' => $projectDTO->name,
            'workspace_id' => $projectDTO->workspaceId,
            'user_id' => $user->id,
        ]);

        if (! $this->workspaceRepository->isUserMemberOfWorkspace(
            $projectDTO->workspaceId,
            $user->id
        )) {
            throw new \InvalidArgumentException('User is not a member of this workspace');
        }

        $project = $this->workspaceRepository->createProject($projectDTO);

        Log::channel('domain')->info('Project created successfully', [
            'project_id' => $project->getId(),
            'workspace_id' => $project->getWorkspaceId(),
        ]);

        return $project;
    }

    public function getProjectById(int $id): ProjectEntity
    {
        $project = $this->workspaceRepository->findProjectById($id);
        if (! $project) {
            throw new \InvalidArgumentException('Project not found');
        }

        return $project;
    }

    public function createTask(TaskDTO $taskDTO, UserModel $user): TaskEntity
    {
        Log::channel('domain')->info('Creating task', [
            'title' => $taskDTO->title,
            'project_id' => $taskDTO->projectId,
            'user_id' => $user->id,
        ]);

        if (! $this->workspaceRepository->isUserMemberOfProject(
            $taskDTO->projectId,
            $user->id
        )) {
            throw new \InvalidArgumentException('User is not a member of this project');
        }

        if ($taskDTO->dueDate && $taskDTO->dueDate->isPast()) {
            throw new \InvalidArgumentException('Due date cannot be in the past');
        }

        $task = $this->workspaceRepository->createTask($taskDTO);

        Log::channel('domain')->info('Task created successfully', [
            'task_id' => $task->getId(),
            'project_id' => $task->getProjectId(),
        ]);

        return $task;
    }

    public function getTaskById(int $id): TaskEntity
    {
        $task = $this->workspaceRepository->findTaskById($id);
        if (! $task) {
            throw new \InvalidArgumentException('Task not found');
        }

        return $task;
    }

    public function completeTask(int $taskId, UserModel $user): TaskEntity
    {
        $task = $this->getTaskById($taskId);

        if (! $this->workspaceRepository->isUserMemberOfProject(
            $task->getProjectId(),
            $user->id
        )) {
            throw new \InvalidArgumentException('User does not have permission to complete this task');
        }

        $completedTask = $task->markAsCompleted();

        $updatedTask = $this->workspaceRepository->updateTask(
            $taskId,
            TaskDTO::fromArray($completedTask->toArray())
        );

        if ($updatedTask === null) {
            Log::channel('domain')->error('Failed to update task to completed status', [
                'task_id' => $taskId,
                'user_id' => $user->id,
            ]);
            throw new \RuntimeException("Task {$taskId} could not be updated to completed status");
        }

        Log::channel('domain')->info('Task completed', [
            'task_id' => $taskId,
            'user_id' => $user->id,
        ]);

        return $updatedTask;
    }

    public function addCommentToTask(int $taskId, string $comment, UserModel $user): void
    {
        if (strlen($comment) < 3) {
            throw new \InvalidArgumentException('Comment must be at least 3 characters');
        }

        $this->workspaceRepository->addCommentToTask($taskId, $comment, $user->id);

        Log::channel('domain')->info('Comment added to task', [
            'task_id' => $taskId,
            'user_id' => $user->id,
        ]);
    }

    public function uploadAttachmentToTask(
        int $taskId,
        string $filePath,
        string $fileName,
        string $mimeType,
        int $fileSize,
        UserModel $user
    ): void {
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (! in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException('Invalid file type');
        }

        if ($fileSize > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException('File size exceeds maximum limit');
        }

        $this->workspaceRepository->uploadAttachmentToTask(
            $taskId,
            $filePath,
            $fileName,
            $mimeType,
            $fileSize,
            $user->id
        );

        Log::channel('domain')->info('Attachment uploaded to task', [
            'task_id' => $taskId,
            'file_name' => $fileName,
            'user_id' => $user->id,
        ]);
    }
}
