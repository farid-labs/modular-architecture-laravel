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
use Modules\Workspace\Domain\Exceptions\AuthorizationException;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Modules\Workspace\Domain\ValueObjects\AttachmentCollection;
use Modules\Workspace\Infrastructure\Jobs\ProcessTaskAttachmentJob;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Workspace Application Service.
 *
 * Orchestrates business logic for workspace operations.
 * Acts as the primary interface between controllers and repositories.
 * Handles authorization, validation, caching, and event dispatching.
 *
 * @see WorkspaceRepositoryInterface For data access operations
 * @see WorkspaceEntity For domain entity representation
 */
class WorkspaceService
{
    /**
     * Create a new WorkspaceService instance.
     *
     * @param  WorkspaceRepositoryInterface  $workspaceRepository  Repository abstraction for workspace-related persistence
     */
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository
    ) {}

    /**
     * Get all workspaces for a specific user.
     *
     * Retrieves workspaces where the user is either owner or member.
     * Includes member count and project count for each workspace.
     *
     * @param  int  $userId  The user ID to fetch workspaces for
     * @return array<int, WorkspaceEntity> Collection of workspace entities
     */
    public function getWorkspacesByUser(int $userId): array
    {
        // Log workspace retrieval request for audit trail
        Log::channel('domain')->debug('Fetching workspaces for user', ['user_id' => $userId]);

        return $this->workspaceRepository->getWorkspacesByUser($userId);
    }

    /**
     * Retrieve a workspace by its slug.
     *
     * Fetches workspace using URL-friendly slug identifier.
     * Throws exception if workspace is not found.
     *
     * @param  string  $slug  The workspace slug
     * @return WorkspaceEntity The workspace entity
     *
     * @throws AuthorizationException If workspace is not found
     */
    public function getWorkspaceBySlug(string $slug): WorkspaceEntity
    {
        // Log slug lookup for audit trail
        Log::channel('domain')->debug('Fetching workspace by slug', ['slug' => $slug]);

        $workspace = $this->workspaceRepository->findBySlug($slug);

        // Throw exception if not found
        if (! $workspace) {
            throw new AuthorizationException('not_found', ['slug' => $slug]);
        }

        return $workspace;
    }

    /**
     * Create a new workspace.
     *
     * Creates workspace and automatically assigns owner as member with 'owner' role.
     * Logs creation attempt and success for audit trail.
     *
     * @param  WorkspaceDTO  $workspaceDTO  The workspace data transfer object
     * @param  UserModel  $user  The user creating the workspace (becomes owner)
     * @return WorkspaceEntity The created workspace entity
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
     *
     * Only workspace owner can perform updates.
     * Supports partial updates by filtering null values.
     *
     * @param  int  $id  The workspace ID
     * @param  WorkspaceDTO  $workspaceDTO  The workspace data transfer object
     * @param  UserModel  $user  The user attempting the update
     * @return WorkspaceEntity The updated workspace entity
     *
     * @throws AuthorizationException If user is not owner or no fields to update
     */
    public function updateWorkspace(int $id, WorkspaceDTO $workspaceDTO, UserModel $user): WorkspaceEntity
    {
        $workspace = $this->getWorkspaceById($id);

        // Check if user is the workspace owner
        if ($workspace->getOwnerId() !== $user->id) {
            throw new AuthorizationException('not_owner');
        }

        // Filter out null fields (partial update support)
        $data = $workspaceDTO->toArray();
        $filteredData = array_filter($data, fn ($value) => $value !== null);

        if (empty($filteredData)) {
            throw new AuthorizationException('no_fields_to_update');
        }

        // Perform update
        $workspace = $this->workspaceRepository->update($id, WorkspaceDTO::fromArray($filteredData));

        if (! $workspace) {
            throw new AuthorizationException('not_found_by_id', ['id' => $id]);
        }

        return $workspace;
    }

    /**
     * Delete a workspace by ID.
     *
     * Permanently removes workspace and all associated data.
     * This action cannot be undone.
     *
     * @param  int  $id  The workspace ID
     * @return bool True if deleted successfully
     *
     * @throws AuthorizationException If workspace is not found
     */
    public function deleteWorkspace(int $id): bool
    {
        // Log deletion attempt
        Log::channel('domain')->info('Deleting workspace', ['workspace_id' => $id]);

        $result = $this->workspaceRepository->delete($id);

        // If not found, log warning and throw exception
        if (! $result) {
            Log::channel('domain')->warning('Workspace not found for deletion', ['workspace_id' => $id]);
            throw new AuthorizationException('not_found_by_id', ['id' => $id]);
        }

        // Log success
        Log::channel('domain')->info('Workspace deleted successfully', ['workspace_id' => $id]);

        return true;
    }

    /**
     * Add a user to a workspace with a specific role.
     *
     * Validates role is one of: owner, admin, member.
     * Updates existing membership or creates new one.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  int  $userId  The user ID to add
     * @param  string  $role  The role to assign (owner, admin, member)
     * @return bool True if added successfully
     *
     * @throws AuthorizationException If role is invalid
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
            throw new AuthorizationException('invalid_role', ['role' => $role]);
        }

        return $this->workspaceRepository->addUserToWorkspace($workspaceId, $userId, $role);
    }

    /**
     * Remove a user from a workspace.
     *
     * Removes user membership and revokes all workspace access.
     * Returns number of affected rows (0 or 1).
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  int  $userId  The user ID to remove
     * @return int Number of affected rows (0 if not found, 1 if successful)
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
     *
     * Only workspace members can list projects.
     * Returns projects ordered by creation date.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  int  $userId  The user ID requesting projects
     * @return array<int, ProjectEntity> Collection of project entities
     *
     * @throws AuthorizationException If user is not a workspace member
     */
    public function getProjectsByWorkspace(int $workspaceId, int $userId): array
    {
        // Ensure user is a workspace member
        if (! $this->workspaceRepository->isUserMemberOfWorkspace($workspaceId, $userId)) {
            throw new AuthorizationException('not_member');
        }

        return $this->workspaceRepository->getProjectsByWorkspace($workspaceId);
    }

    /**
     * Create a new project inside a workspace.
     *
     * Validates workspace exists and user is a member.
     * Logs creation attempt and success for audit trail.
     *
     * @param  ProjectDTO  $projectDTO  The project data transfer object
     * @param  UserModel  $user  The user creating the project
     * @return ProjectEntity The created project entity
     *
     * @throws AuthorizationException If workspace doesn't exist or user is not a member
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
            throw new AuthorizationException(
                'not_found_by_id',
                ['id' => $projectDTO->workspaceId]
            );
        }

        // Ensure user is a member of the workspace
        if (! $this->workspaceRepository->isUserMemberOfWorkspace(
            $projectDTO->workspaceId,
            $user->id
        )) {
            throw new AuthorizationException('not_member');
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
     *
     * Fetches project details including workspace association.
     * Throws exception if project is not found.
     *
     * @param  int  $id  The project ID
     * @return ProjectEntity The project entity
     *
     * @throws AuthorizationException If project is not found
     */
    public function getProjectById(int $id): ProjectEntity
    {
        $project = $this->workspaceRepository->findProjectById($id);

        if (! $project) {
            throw new AuthorizationException('project_not_found', ['id' => $id]);
        }

        return $project;
    }

    /**
     * Create a new task inside a project.
     *
     * Validates project exists and user is a member.
     * Ensures due date is not in the past.
     *
     * @param  TaskDTO  $taskDTO  The task data transfer object
     * @param  UserModel  $user  The user creating the task
     * @return TaskEntity The created task entity
     *
     * @throws AuthorizationException If project doesn't exist, user is not a member, or due date is in past
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
            throw new AuthorizationException('not_member_project');
        }

        // Validate due date is not in the past
        if ($taskDTO->dueDate && $taskDTO->dueDate->isPast()) {
            throw new AuthorizationException('date_cannot_past');
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
     *
     * Fetches task details including project association.
     * Throws exception if task is not found.
     *
     * @param  int  $id  The task ID
     * @return TaskEntity The task entity
     *
     * @throws AuthorizationException If task is not found
     */
    public function getTaskById(int $id): TaskEntity
    {
        $task = $this->workspaceRepository->findTaskById($id);

        if (! $task) {
            throw new AuthorizationException('task_not_found', ['id' => $id]);
        }

        return $task;
    }

    /**
     * Mark a task as completed.
     *
     * Updates task status to COMPLETED using immutable entity pattern.
     * Validates user is a member of the project.
     *
     * @param  int  $taskId  The task ID
     * @param  UserModel  $user  The user completing the task
     * @return TaskEntity The completed task entity
     *
     * @throws AuthorizationException If user is not a member or update fails
     */
    public function completeTask(int $taskId, UserModel $user): TaskEntity
    {
        $task = $this->getTaskById($taskId);

        // Ensure user is a member of the project
        if (! $this->workspaceRepository->isUserMemberOfProject(
            $task->getProjectId(),
            $user->id
        )) {
            throw new AuthorizationException('not_member_of_workspace');
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

            throw new AuthorizationException('task_update_fail', ['taskId' => $taskId]);
        }

        // Log completion
        Log::channel('domain')->info('Task completed', [
            'task_id' => $taskId,
            'user_id' => $user->id,
        ]);

        return $updatedTask;
    }

    /**
     * Add a comment to a task and dispatch domain event.
     *
     * Validates comment length and user membership.
     * Dispatches TaskCommentAdded event for real-time notifications.
     *
     * @param  int  $taskId  The task ID
     * @param  string  $comment  The comment content
     * @param  UserModel  $user  The user adding the comment
     * @return TaskCommentEntity The created comment entity
     *
     * @throws AuthorizationException If comment is too short
     * @throws AccessDeniedHttpException If user is not a project member
     */
    public function addCommentToTask(int $taskId, string $comment, UserModel $user): TaskCommentEntity
    {
        if (strlen($comment) < 3) {
            throw new AuthorizationException('comment_min_length');
        }

        Log::channel('domain')->info('Adding comment to task', [
            'task_id' => $taskId,
            'user_id' => $user->id,
        ]);

        // Dispatch domain event
        $task = $this->getTaskById($taskId);

        if (! $this->workspaceRepository->isUserMemberOfProject($task->getProjectId(), $user->id)) {
            throw new AccessDeniedHttpException(
                __('not_member_project')
            );
        }

        $commentEntity = $this->workspaceRepository->addCommentToTask($taskId, $comment, $user->id);

        // Dispatch event for real-time notification
        event(new \Modules\Workspace\Domain\Events\TaskCommentAdded($task, $commentEntity, $user->id));

        return $commentEntity;
    }

    /**
     * Get a single attachment by its ID.
     *
     * Fetches attachment details including file metadata and uploader.
     * Throws exception if attachment is not found.
     *
     * @param  int  $attachmentId  The attachment ID
     * @return TaskAttachmentEntity The attachment entity
     *
     * @throws AuthorizationException If attachment is not found
     */
    public function getAttachmentById(int $attachmentId): TaskAttachmentEntity
    {
        $attachment = $this->workspaceRepository->findAttachmentById($attachmentId);

        if (! $attachment) {
            throw new AuthorizationException('attachment_not_found', ['id' => $attachmentId]);
        }

        return $attachment;
    }

    /**
     * Upload multiple attachments to a task.
     *
     * Handles multiple file upload with domain validation:
     * - AttachmentCollection enforces max 3 files rule
     * - FileSize value object enforces 10MB limit
     * - Each file stored and recorded as TaskAttachmentEntity
     * - Jobs dispatched for async processing
     * - Events dispatched for real-time notifications
     *
     * @param  int  $taskId  The task ID
     * @param  AttachmentCollection  $collection  The attachment collection value object
     * @param  UserModel  $user  The user uploading the attachments
     * @return array<int, TaskAttachmentEntity> Collection of created attachment entities
     *
     * @throws AuthorizationException If validation fails or user lacks permission
     */
    public function uploadAttachmentsToTask(
        int $taskId,
        AttachmentCollection $collection,
        UserModel $user
    ): array {
        // Validate task exists
        $task = $this->getTaskById($taskId);

        Log::channel('domain')->info('Uploading multiple attachments to task', [
            'task_id' => $taskId,
            'user_id' => $user->id,
            'file_count' => $collection->count(),
            'total_size' => $collection->totalSize()->formatted(),
        ]);

        $attachments = [];

        // Process each attachment in the collection
        foreach ($collection as $upload) {
            // Store file
            $storedPath = $upload->getFile()->store('task-attachments');

            if ($storedPath === false) {
                throw new AuthorizationException('file_storage_failed');
            }

            // Create attachment entity
            $attachment = $this->workspaceRepository->uploadAttachmentToTask(
                $taskId,
                $storedPath,
                $upload->getFileName()->value(),
                $upload->getMimeType(),
                $upload->getFileSize()->bytes(),
                $user->id
            );

            $attachments[] = $attachment;

            // Dispatch job for async processing
            dispatch(new ProcessTaskAttachmentJob($attachment, $storedPath));
        }

        // Dispatch event for real-time notification (supports multiple attachments)
        event(new TaskAttachmentUploaded($task, $attachments, $user->id));

        return $attachments;
    }

    /**
     * Get all comments for a task with authorization.
     *
     * Caches results for 10 minutes to improve performance.
     * Validates user is a member of the project.
     *
     * @param  int  $taskId  The task ID
     * @param  int  $userId  The user ID requesting comments
     * @return array<int, TaskCommentEntity> Collection of comment entities
     *
     * @throws AuthorizationException If user is not a project member
     */
    public function getCommentsByTask(int $taskId, int $userId): array
    {
        $task = $this->getTaskById($taskId);

        if (! $this->workspaceRepository->isUserMemberOfProject($task->getProjectId(), $userId)) {
            throw new AuthorizationException('not_member_of_project');
        }

        return Cache::remember(
            "task:{$taskId}:comments",
            now()->addMinutes(10),
            fn () => $this->workspaceRepository->getCommentsByTask($taskId, $userId)
        );
    }

    /**
     * Update comment (only owner within 30 minutes).
     *
     * Repository enforces ownership and time window restrictions.
     * Validates comment length before update.
     *
     * @param  int  $commentId  The comment ID
     * @param  string  $newComment  The new comment content
     * @param  int  $userId  The user ID attempting the update
     * @return TaskCommentEntity The updated comment entity
     *
     * @throws AuthorizationException If comment is too short
     */
    public function updateComment(int $commentId, string $newComment, int $userId): TaskCommentEntity
    {
        if (strlen($newComment) < 3) {
            throw new AuthorizationException('comment_min_length');
        }

        return $this->workspaceRepository->updateComment($commentId, $newComment, $userId);
    }

    /**
     * Get all attachments for a task with authorization enforcement.
     *
     * Validates user is a member of the project before returning attachments.
     * Prevents information disclosure to unauthorized users.
     *
     * @param  int  $taskId  The task ID
     * @param  int  $userId  The user ID requesting attachments (for authorization)
     * @return array<int, TaskAttachmentEntity> Collection of attachment entities
     *
     * @throws AuthorizationException If user is not authorized to view task attachments
     */
    public function getAttachmentsByTask(int $taskId, int $userId): array
    {
        // Validate task existence first
        $task = $this->getTaskById($taskId);

        // CRITICAL AUTHORIZATION CHECK: Ensure user is member of task's project
        if (! $this->workspaceRepository->isUserMemberOfProject($task->getProjectId(), $userId)) {
            throw new AuthorizationException('not_member_of_project');
        }

        // Returns cached attachments after authorization passes
        return Cache::remember(
            "task:{$taskId}:attachments",
            now()->addMinutes(15),
            fn () => $this->workspaceRepository->getAttachmentsByTask($taskId)
        );
    }

    /**
     * Update an existing project.
     *
     * Validates user is a member of the workspace.
     * Supports partial updates.
     *
     * @param  int  $id  The project ID
     * @param  ProjectDTO  $projectDTO  The project data transfer object
     * @param  UserModel  $user  The user updating the project
     * @return ProjectEntity The updated project entity
     *
     * @throws AuthorizationException If user is not a member or project not found
     */
    public function updateProject(int $id, ProjectDTO $projectDTO, UserModel $user): ProjectEntity
    {
        $project = $this->getProjectById($id);

        // Check if user is member of workspace
        if (! $this->workspaceRepository->isUserMemberOfWorkspace($project->getWorkspaceId(), $user->id)) {
            throw new AuthorizationException('not_member');
        }

        $updatedProject = $this->workspaceRepository->updateProject($id, $projectDTO);

        if (! $updatedProject) {
            throw new AuthorizationException('project_not_found', ['id' => $id]);
        }

        Log::channel('domain')->info('Project updated', [
            'project_id' => $id,
            'user_id' => $user->id,
        ]);

        return $updatedProject;
    }

    /**
     * Delete a project by ID.
     *
     * Permanently removes project and all associated tasks.
     * This action cannot be undone.
     *
     * @param  int  $id  The project ID
     * @return bool True if deleted successfully
     *
     * @throws AuthorizationException If project is not found
     */
    public function deleteProject(int $id): bool
    {
        $project = $this->getProjectById($id);

        $result = $this->workspaceRepository->deleteProject($id);

        if (! $result) {
            throw new AuthorizationException('project_not_found', ['id' => $id]);
        }

        Log::channel('domain')->info('Project deleted', ['project_id' => $id]);

        return true;
    }

    /**
     * Get all tasks for a project.
     *
     * Validates user is a member of the workspace.
     * Returns tasks ordered by creation date.
     *
     * @param  int  $projectId  The project ID
     * @param  int  $userId  The user ID requesting tasks
     * @return array<int, TaskEntity> Collection of task entities
     *
     * @throws AuthorizationException If user is not a workspace member
     */
    public function getTasksByProject(int $projectId, int $userId): array
    {
        // Ensure project exists
        $project = $this->getProjectById($projectId);

        // Ensure user is a member of the workspace
        if (! $this->workspaceRepository->isUserMemberOfWorkspace($project->getWorkspaceId(), $userId)) {
            throw new AuthorizationException('not_member_of_project');
        }

        return $this->workspaceRepository->getTasksByProject($projectId);
    }

    /**
     * Update an existing task.
     *
     * Validates user is a member of the project.
     * Supports partial updates.
     *
     * @param  int  $id  The task ID
     * @param  TaskDTO  $taskDTO  The task data transfer object
     * @param  UserModel  $user  The user updating the task
     * @return TaskEntity The updated task entity
     *
     * @throws AuthorizationException If user is not a member or task not found
     */
    public function updateTask(int $id, TaskDTO $taskDTO, UserModel $user): TaskEntity
    {
        $task = $this->getTaskById($id);

        if (! $this->workspaceRepository->isUserMemberOfProject($task->getProjectId(), $user->id)) {
            throw new AuthorizationException('not_member_of_project');
        }

        $updatedTask = $this->workspaceRepository->updateTask($id, $taskDTO);

        if (! $updatedTask) {
            throw new AuthorizationException('task_not_found', ['id' => $id]);
        }

        Log::channel('domain')->info('Task updated', [
            'task_id' => $id,
            'user_id' => $user->id,
        ]);

        return $updatedTask;
    }

    /**
     * Delete a task by ID.
     *
     * Permanently removes task and all associated comments/attachments.
     * This action cannot be undone.
     *
     * @param  int  $id  The task ID
     * @return bool True if deleted successfully
     *
     * @throws AuthorizationException If task is not found
     */
    public function deleteTask(int $id): bool
    {
        $task = $this->getTaskById($id);

        $result = $this->workspaceRepository->deleteTask($id);

        if (! $result) {
            throw new AuthorizationException('task_not_found', ['id' => $id]);
        }

        Log::channel('domain')->info('Task deleted', ['task_id' => $id]);

        return true;
    }

    /**
     * Get a single comment by its ID.
     *
     * Fetches comment details including author information.
     * Throws exception if comment is not found.
     *
     * @param  int  $commentId  The comment ID
     * @return TaskCommentEntity The comment entity
     *
     * @throws AuthorizationException If comment is not found
     */
    public function getCommentById(int $commentId): TaskCommentEntity
    {
        $comment = $this->workspaceRepository->findCommentById($commentId);
        if (! $comment) {
            throw new AuthorizationException('comment_not_found', ['id' => $commentId]);
        }

        return $comment;
    }

    /**
     * Get workspace members.
     *
     * Validates user is a member of the workspace.
     * Returns member details including roles and join dates.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  int  $userId  The user ID requesting members
     * @return array<int, array<string, mixed>> Collection of member data arrays
     *
     * @throws AuthorizationException If user is not a workspace member
     */
    public function getWorkspaceMembers(int $workspaceId, int $userId): array
    {
        if (! $this->workspaceRepository->isUserMemberOfWorkspace($workspaceId, $userId)) {
            throw new AuthorizationException('not_member');
        }

        return $this->workspaceRepository->getWorkspaceMembers($workspaceId);
    }

    /**
     * Delete a comment.
     *
     * Repository enforces ownership and task relationship restrictions.
     *
     * @param  int  $commentId  The comment ID
     * @param  int  $taskId  The task ID (for validation)
     * @param  int  $userId  The user ID attempting deletion
     * @return bool True if deleted successfully
     */
    public function deleteComment(int $taskId, int $commentId, int $userId): bool
    {
        $task = $this->getTaskById($taskId);

        $comment = $this->workspaceRepository->findCommentById($commentId);
        if (! $comment || $comment->getTaskId() !== $taskId) {
            throw new AuthorizationException('comment_not_found', ['id' => $commentId]);
        }

        return $this->workspaceRepository->deleteComment($taskId, $commentId, $userId);
    }

    /**
     * Delete an attachment.
     *
     * Repository enforces ownership restriction.
     * Only attachment uploader can delete their own files.
     *
     * @param  int  $attachmentId  The attachment ID
     * @param  int  $userId  The user ID attempting deletion
     * @return bool True if deleted successfully
     */
    public function deleteAttachment(int $attachmentId, int $userId): bool
    {
        return $this->workspaceRepository->deleteAttachment($attachmentId, $userId);
    }

    /**
     * Get workspace by ID.
     *
     * Fetches workspace details including owner and members.
     * Throws exception if workspace is not found.
     *
     * @param  int  $id  The workspace ID
     * @return WorkspaceEntity The workspace entity
     *
     * @throws AuthorizationException If workspace is not found
     */
    public function getWorkspaceById(int $id): WorkspaceEntity
    {
        $workspace = $this->workspaceRepository->findById($id);

        if (! $workspace) {
            throw new AuthorizationException('not_found_by_id', ['id' => $id]);
        }

        return $workspace;
    }
}
