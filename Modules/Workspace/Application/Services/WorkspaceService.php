<?php

namespace Modules\Workspace\Application\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Application\DTOs\ProjectDTO;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Entities\WorkspaceEntity;
use Modules\Workspace\Domain\Events\TaskAttachmentUploaded;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Modules\Workspace\Infrastructure\Jobs\ProcessTaskAttachmentJob;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WorkspaceService
{
    public function __construct(
        // Repository abstraction for workspace-related persistence
        private WorkspaceRepositoryInterface $workspaceRepository
    ) {}

    /**
     * Get all workspaces for a specific user.
     *
     * @return array<int, WorkspaceEntity>
     */
    public function getWorkspacesByUser(int $userId): array
    {
        // Log workspace retrieval request
        Log::channel('domain')->debug('Fetching workspaces for user', ['user_id' => $userId]);

        return $this->workspaceRepository->getWorkspacesByUser($userId);
    }

    /**
     * Retrieve a workspace by its slug.
     */
    public function getWorkspaceBySlug(string $slug): WorkspaceEntity
    {
        // Log slug lookup
        Log::channel('domain')->debug('Fetching workspace by slug', ['slug' => $slug]);

        $workspace = $this->workspaceRepository->findBySlug($slug);

        // Throw exception if not found
        if (! $workspace) {
            throw new \InvalidArgumentException(__('workspaces.not_found', ['slug' => $slug]));
        }

        return $workspace;
    }

    /**
     * Create a new workspace.
     */
    public function createWorkspace(WorkspaceDTO $workspaceDTO, UserModel $user): WorkspaceEntity
    {
        // Log workspace creation attempt
        Log::channel('domain')->info('Creating workspace', [
            'name' => $workspaceDTO->name,
            'owner_id' => $user->id,
        ]);

        // Prepare data and enforce owner ID
        $data = $workspaceDTO->toArray();
        Log::channel('domain')->info('Data workspace', $data);

        $data['owner_id'] = $user->id;
        $workspaceDTO = WorkspaceDTO::fromArray($data);

        // Persist workspace
        $workspace = $this->workspaceRepository->create($workspaceDTO);

        // Log success
        Log::channel('domain')->info('Workspace created successfully', [
            'workspace_id' => $workspace->getId(),
            'owner_id' => $user->id,
        ]);

        return $workspace;
    }

    /**
     * Update an existing workspace.
     */
    public function updateWorkspace(int $id, WorkspaceDTO $workspaceDTO): WorkspaceEntity
    {
        // Filter out null fields (partial update support)
        $data = $workspaceDTO->toArray();
        $filteredData = array_filter($data, fn ($value) => $value !== null);

        if (empty($filteredData)) {
            throw new \InvalidArgumentException(__('workspaces.no_fields_to_update'));
        }

        // Perform update
        $workspace = $this->workspaceRepository->update($id, WorkspaceDTO::fromArray($filteredData));

        if (! $workspace) {
            throw new \InvalidArgumentException(__('workspaces.not_found_by_id', ['id' => $id]));
        }

        return $workspace;
    }

    /**
     * Delete a workspace by ID.
     */
    public function deleteWorkspace(int $id): bool
    {
        // Log deletion attempt
        Log::channel('domain')->info('Deleting workspace', ['workspace_id' => $id]);

        $result = $this->workspaceRepository->delete($id);

        // If not found, log warning and throw exception
        if (! $result) {
            Log::channel('domain')->warning('Workspace not found for deletion', ['workspace_id' => $id]);
            throw new \InvalidArgumentException(__('workspaces.not_found_by_id', ['id' => $id]));
        }

        // Log success
        Log::channel('domain')->info('Workspace deleted successfully', ['workspace_id' => $id]);

        return true;
    }

    /**
     * Add a user to a workspace with a specific role.
     */
    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool
    {
        // Log membership addition
        Log::channel('domain')->info('Adding user to workspace', [
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'role' => $role,
        ]);

        // Validate role
        $validRoles = ['owner', 'admin', 'member'];
        if (! in_array($role, $validRoles)) {
            throw new \InvalidArgumentException(__('workspaces.invalid_role', ['role' => $role]));
        }

        return $this->workspaceRepository->addUserToWorkspace($workspaceId, $userId, $role);
    }

    /**
     * Remove a user from a workspace.
     *
     * @return int Number of affected rows
     */
    public function removeUserFromWorkspace(int $workspaceId, int $userId): int
    {
        // Log removal attempt
        Log::channel('domain')->info('Removing user from workspace', [
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
        ]);

        $affected = $this->workspaceRepository->removeUserFromWorkspace($workspaceId, $userId);

        // Log result
        if ($affected == 0) {
            Log::channel('domain')->warning('No membership found to remove', [
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
            ]);
        } else {
            Log::channel('domain')->info('User removed from workspace successfully', [
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
            ]);
        }

        // Returns 0 if nothing was deleted, 1 if successful
        return $affected;
    }

    /**
     * Get all projects belonging to a workspace.
     * Only members can list projects.
     *
     * @return array<int, ProjectEntity>
     */
    public function getProjectsByWorkspace(int $workspaceId, int $userId): array
    {
        // Ensure user is a workspace member
        if (! $this->workspaceRepository->isUserMemberOfWorkspace($workspaceId, $userId)) {
            throw new \InvalidArgumentException(__('workspaces.not_member'));
        }

        return $this->workspaceRepository->getProjectsByWorkspace($workspaceId);
    }

    /**
     * Create a new project inside a workspace.
     */
    public function createProject(ProjectDTO $projectDTO, UserModel $user): ProjectEntity
    {
        // Log project creation attempt
        Log::channel('domain')->info('Creating project', [
            'name' => $projectDTO->name,
            'workspace_id' => $projectDTO->workspaceId,
            'user_id' => $user->id,
        ]);

        // Ensure workspace exists
        if (! $this->workspaceRepository->workspaceExists($projectDTO->workspaceId)) {
            throw new \InvalidArgumentException(
                __('workspaces.not_found_by_id', ['id' => $projectDTO->workspaceId])
            );
        }

        // Ensure user is a member of the workspace
        if (! $this->workspaceRepository->isUserMemberOfWorkspace(
            $projectDTO->workspaceId,
            $user->id
        )) {
            throw new \InvalidArgumentException(__('workspaces.not_member'));
        }

        $project = $this->workspaceRepository->createProject($projectDTO);

        // Log success
        Log::channel('domain')->info('Project created successfully', [
            'project_id' => $project->getId(),
            'workspace_id' => $project->getWorkspaceId(),
        ]);

        return $project;
    }

    /**
     * Retrieve a project by ID.
     */
    public function getProjectById(int $id): ProjectEntity
    {
        $project = $this->workspaceRepository->findProjectById($id);

        if (! $project) {
            throw new \InvalidArgumentException(__('workspaces.project_not_found', ['id' => $id]));
        }

        return $project;
    }

    /**
     * Create a new task inside a project.
     */
    public function createTask(TaskDTO $taskDTO, UserModel $user): TaskEntity
    {
        // Ensure project exists
        $this->getProjectById($taskDTO->projectId);

        // Log task creation attempt
        Log::channel('domain')->info('Creating task', [
            'title' => $taskDTO->title,
            'project_id' => $taskDTO->projectId,
            'user_id' => $user->id,
        ]);

        // Ensure user is a member of the project
        if (! $this->workspaceRepository->isUserMemberOfProject(
            $taskDTO->projectId,
            $user->id
        )) {
            throw new \InvalidArgumentException(__('workspaces.not_member_project'));
        }

        // Validate due date is not in the past
        if ($taskDTO->dueDate && $taskDTO->dueDate->isPast()) {
            throw new \InvalidArgumentException(__('workspaces.date_cannot_past'));
        }

        $task = $this->workspaceRepository->createTask($taskDTO);

        // Log success
        Log::channel('domain')->info('Task created successfully', [
            'task_id' => $task->getId(),
            'project_id' => $task->getProjectId(),
        ]);

        return $task;
    }

    /**
     * Retrieve a task by ID.
     */
    public function getTaskById(int $id): TaskEntity
    {
        $task = $this->workspaceRepository->findTaskById($id);

        if (! $task) {
            throw new \InvalidArgumentException(__('workspaces.task_not_found', ['id' => $id]));
        }

        return $task;
    }

    /**
     * Mark a task as completed.
     */
    public function completeTask(int $taskId, UserModel $user): TaskEntity
    {
        $task = $this->getTaskById($taskId);

        // Ensure user is a member of the project
        if (! $this->workspaceRepository->isUserMemberOfProject(
            $task->getProjectId(),
            $user->id
        )) {
            throw new \InvalidArgumentException(__('workspaces.not_member_of_workspace'));
        }

        // Update domain entity state
        $completedTask = $task->markAsCompleted();

        // Persist updated state
        $updatedTask = $this->workspaceRepository->updateTask(
            $taskId,
            TaskDTO::fromArray($completedTask->toArray())
        );

        if ($updatedTask === null) {
            Log::channel('domain')->error('Failed to update task to completed status', [
                'task_id' => $taskId,
                'user_id' => $user->id,
            ]);

            throw new \InvalidArgumentException(__('workspaces.task_update_fail', ['taskId' => $taskId]));
        }

        // Log completion
        Log::channel('domain')->info('Task completed', [
            'task_id' => $taskId,
            'user_id' => $user->id,
        ]);

        return $updatedTask;
    }

    /**
     * Add a comment to a task + dispatch domain event
     */
    public function addCommentToTask(int $taskId, string $comment, UserModel $user): TaskCommentEntity
    {
        if (strlen($comment) < 3) {
            throw new \InvalidArgumentException(__('workspaces.comment_min_length'));
        }

        Log::channel('domain')->info('Adding comment to task', [
            'task_id' => $taskId,
            'user_id' => $user->id,
        ]);

        // Dispatch domain event
        $task = $this->getTaskById($taskId);

        if (! $this->workspaceRepository->isUserMemberOfProject($task->getProjectId(), $user->id)) {
            throw new AccessDeniedHttpException(
                __('workspaces.not_member_project')
            );
        }
        $commentEntity = $this->workspaceRepository->addCommentToTask($taskId, $comment, $user->id);
        event(new \Modules\Workspace\Domain\Events\TaskCommentAdded($task, $commentEntity, $user->id));

        return $commentEntity;
    }

    /**
     * Upload attachment + dispatch domain event
     */
    public function uploadAttachmentToTask(
        int $taskId,
        string $filePath,
        string $fileName,
        string $mimeType,
        int $fileSize,
        UserModel $user
    ): TaskAttachmentEntity {
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (! in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException(__('workspaces.invalid_file_type'));
        }
        if ($fileSize > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException(__('workspaces.file_size_exceeds_limit'));
        }

        Log::channel('domain')->info('Uploading attachment to task', [
            'task_id' => $taskId,
            'file_name' => $fileName,
            'user_id' => $user->id,
        ]);

        $attachmentEntity = $this->workspaceRepository->uploadAttachmentToTask(
            $taskId,
            $filePath,
            $fileName,
            $mimeType,
            $fileSize,
            $user->id
        );

        // Dispatch job (thumbnail, virus scan, move to S3, etc.)
        dispatch(new ProcessTaskAttachmentJob($attachmentEntity, $filePath));

        // Dispatch event for real-time notification
        $task = $this->getTaskById($taskId);
        event(new TaskAttachmentUploaded($task, $attachmentEntity, $user->id));

        return $attachmentEntity;
    }

    /**
     * Get all comments for a task (with authorization)
     *
     * @return array<int, TaskCommentEntity>
     */
    public function getCommentsByTask(int $taskId, int $userId): array
    {
        $task = $this->getTaskById($taskId);
        if (! $this->workspaceRepository->isUserMemberOfProject($task->getProjectId(), $userId)) {
            throw new \InvalidArgumentException(__('workspaces.not_member_project'));
        }

        return Cache::remember(
            "task:{$taskId}:comments",
            now()->addMinutes(10),
            fn () => $this->workspaceRepository->getCommentsByTask($taskId, $userId)
        );
    }

    /**
     * Update comment (only owner + within 30 minutes)
     */
    public function updateComment(int $commentId, string $newComment, int $userId): TaskCommentEntity
    {
        if (strlen($newComment) < 3) {
            throw new \InvalidArgumentException(__('workspaces.comment_min_length'));
        }

        return $this->workspaceRepository->updateComment($commentId, $newComment, $userId);
    }

    /**
     * Get all attachments for a task
     *
     * @return array<int, TaskAttachmentEntity>
     */
    public function getAttachmentsByTask(int $taskId): array
    {
        return Cache::remember(
            "task:{$taskId}:attachments",
            now()->addMinutes(15),
            fn () => $this->workspaceRepository->getAttachmentsByTask($taskId)
        );
    }
}
