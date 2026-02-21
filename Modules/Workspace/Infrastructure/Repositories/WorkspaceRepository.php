<?php

namespace Modules\Workspace\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Application\DTOs\ProjectDTO;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Entities\WorkspaceEntity;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Modules\Workspace\Domain\ValueObjects\FileName;
use Modules\Workspace\Domain\ValueObjects\FilePath;
use Modules\Workspace\Domain\ValueObjects\TaskTitle;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskAttachmentModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskCommentModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceMemberPivot;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;

/**
 * Workspace repository implementation.
 *
 * Handles data access and mapping between persistence models and domain entities.
 * Implements the Repository pattern for clean separation of concerns.
 * All methods work with domain entities, not Eloquent models directly.
 *
 * @see WorkspaceRepositoryInterface For interface contract
 * @see WorkspaceEntity For domain entity representation
 */
class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    /**
     * Create a new repository instance.
     *
     * @param  string  $modelClass  The workspace model class name for dependency injection
     */
    public function __construct(private string $modelClass = WorkspaceModel::class) {}

    /**
     * Get a new workspace model instance.
     *
     * @return WorkspaceModel New model instance
     */
    private function getModel(): WorkspaceModel
    {
        /** @var WorkspaceModel */
        return new $this->modelClass;
    }

    /**
     * Find workspace by its ID.
     *
     * @param  int  $id  The workspace ID
     */
    public function findById(int $id): ?WorkspaceEntity
    {
        /** @var WorkspaceModel|null $model */
        $model = $this->getModel()->with(['owner', 'members'])->find($id);

        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * Find workspace by its slug.
     *
     * @param  string  $slug  The workspace slug
     */
    public function findBySlug(string $slug): ?WorkspaceEntity
    {
        /** @var WorkspaceModel|null $model */
        $model = $this->getModel()->with(['owner', 'members'])->where('slug', $slug)->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * Find all workspaces owned by a specific user.
     *
     * @param  int  $ownerId  The owner user ID
     * @return array<int, WorkspaceEntity>
     */
    public function findByOwnerId(int $ownerId): array
    {
        /** @var Collection<int, WorkspaceModel> $models */
        $models = $this->getModel()->with(['members'])->where('owner_id', $ownerId)->get();

        return $models->map(fn (WorkspaceModel $m) => $this->mapToEntity($m))->toArray();
    }

    /**
     * Create a new workspace.
     *
     * @param  WorkspaceDTO  $workspaceDTO  The workspace data transfer object
     */
    public function create(WorkspaceDTO $workspaceDTO): WorkspaceEntity
    {
        $data = $workspaceDTO->toArray();

        /** @var WorkspaceModel $model */
        $model = $this->getModel()->create($data);

        // Attach owner as a member with 'owner' role
        $model->members()->attach($data['owner_id'], [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return $this->mapToEntity($model);
    }

    /**
     * Update an existing workspace.
     *
     * @param  int  $id  The workspace ID
     * @param  WorkspaceDTO  $workspaceDTO  The workspace data transfer object
     */
    public function update(int $id, WorkspaceDTO $workspaceDTO): ?WorkspaceEntity
    {
        /** @var WorkspaceModel|null $model */
        $model = $this->getModel()->find($id);

        if (! $model) {
            return null;
        }

        $data = array_filter($workspaceDTO->toArray(), fn ($value) => $value !== null);
        $model->update($data);

        return $this->mapToEntity($model);
    }

    /**
     * Delete a workspace by ID.
     *
     * @param  int  $id  The workspace ID
     */
    public function delete(int $id): bool
    {
        /** @var WorkspaceModel|null $model */
        $model = $this->getModel()->find($id);

        return $model ? (bool) $model->delete() : false;
    }

    /**
     * Get all workspaces.
     *
     * @return array<int, WorkspaceEntity>
     */
    public function getAll(): array
    {
        /** @var Collection<int, WorkspaceModel> $models */
        $models = $this->getModel()->with(['owner'])->get();

        return $models->map(fn (WorkspaceModel $m) => $this->mapToEntity($m))->toArray();
    }

    /**
     * Get all workspaces a user is a member of.
     *
     * @param  int  $userId  The user ID
     * @return array<int, WorkspaceEntity>
     */
    public function getWorkspacesByUser(int $userId): array
    {
        /** @var Collection<int, WorkspaceModel> $models */
        $models = $this->getModel()->with(['members', 'owner'])
            ->withCount(['members', 'projects'])
            ->where(function ($query) use ($userId) {
                $query->where('owner_id', $userId)
                    ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
            })
            ->get();

        return $models->map(fn (WorkspaceModel $m) => $this->mapToEntity($m))->toArray();
    }

    /**
     * Add a user to a workspace with a role.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  int  $userId  The user ID
     * @param  string  $role  The role to assign
     */
    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool
    {
        /** @var WorkspaceModel|null $model */
        $model = $this->getModel()->find($workspaceId);

        if (! $model) {
            return false;
        }

        $isMember = $model->members()->where('user_id', $userId)->exists();

        if ($isMember) {
            $model->members()->updateExistingPivot($userId, [
                'role' => $role,
                'joined_at' => now(),
            ]);

            return true;
        }

        $model->members()->attach($userId, [
            'role' => $role,
            'joined_at' => now(),
        ]);

        return true;
    }

    /**
     * Remove a user from a workspace.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  int  $userId  The user ID
     * @return int Number of affected rows
     */
    public function removeUserFromWorkspace(int $workspaceId, int $userId): int
    {
        /** @var WorkspaceModel|null $model */
        $model = $this->getModel()->find($workspaceId);

        if (! $model) {
            return 0;
        }

        return $model->members()->detach($userId);
    }

    /**
     * Map workspace model to workspace entity.
     *
     * @param  WorkspaceModel  $model  The workspace model
     */
    private function mapToEntity(WorkspaceModel $model): WorkspaceEntity
    {
        $createdAt = $model->created_at ?? now();
        $updatedAt = $model->updated_at ?? now();

        return new WorkspaceEntity(
            $model->id,
            $model->name,
            $model->slug,
            $model->description,
            $model->status,
            $model->owner_id,
            $createdAt,
            $updatedAt,
            $model->members_count ?? 0,
            $model->projects_count ?? 0
        );
    }

    /**
     * Find project by ID.
     *
     * @param  int  $id  The project ID
     */
    public function findProjectById(int $id): ?ProjectEntity
    {
        /** @var ProjectModel|null $model */
        $model = ProjectModel::with(['workspace'])->find($id);

        return $model ? $this->mapProjectToEntity($model) : null;
    }

    /**
     * Get all projects belonging to a workspace.
     *
     * @param  int  $workspaceId  The workspace ID
     * @return array<int, ProjectEntity>
     */
    public function getProjectsByWorkspace(int $workspaceId): array
    {
        /** @var Collection<int, ProjectModel> $models */
        $models = ProjectModel::where('workspace_id', $workspaceId)->get();

        return $models->map(fn (ProjectModel $model) => $this->mapProjectToEntity($model))->all();
    }

    /**
     * Create a new project.
     *
     * @param  ProjectDTO  $projectDTO  The project data transfer object
     */
    public function createProject(ProjectDTO $projectDTO): ProjectEntity
    {
        /** @var ProjectModel $model */
        $model = ProjectModel::create($projectDTO->toArray());

        return $this->mapProjectToEntity($model);
    }

    /**
     * Check if workspace exists by ID.
     *
     * @param  int  $workspaceId  The workspace ID
     */
    public function workspaceExists(int $workspaceId): bool
    {
        return $this->getModel()->where('id', $workspaceId)->exists();
    }

    /**
     * Check if user is a member of an existing workspace.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  int  $userId  The user ID
     *
     * @throws InvalidArgumentException if workspace does not exist
     */
    public function isUserMemberOfWorkspace(int $workspaceId, int $userId): bool
    {
        if (! $this->workspaceExists($workspaceId)) {
            throw new InvalidArgumentException(__('workspaces.not_found_by_id', ['id' => $workspaceId]));
        }

        return $this->getModel()
            ->where('id', $workspaceId)
            ->whereHas('members', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->exists();
    }

    /**
     * Find task by ID.
     *
     * @param  int  $id  The task ID
     */
    public function findTaskById(int $id): ?TaskEntity
    {
        /** @var TaskModel|null $model */
        $model = TaskModel::with(['project', 'assignedUser', 'comments', 'attachments'])->find($id);

        return $model ? $this->mapTaskToEntity($model) : null;
    }

    /**
     * Create a new task.
     *
     * @param  TaskDTO  $taskDTO  The task data transfer object
     */
    public function createTask(TaskDTO $taskDTO): TaskEntity
    {
        /** @var TaskModel $model */
        $model = TaskModel::create($taskDTO->toArray());

        return $this->mapTaskToEntity($model);
    }

    /**
     * Update a task.
     *
     * @param  int  $id  The task ID
     * @param  TaskDTO  $taskDTO  The task data transfer object
     */
    public function updateTask(int $id, TaskDTO $taskDTO): ?TaskEntity
    {
        /** @var TaskModel|null $model */
        $model = TaskModel::query()->find($id);

        if (! $model) {
            return null;
        }

        // Filter null values before update
        $data = array_filter($taskDTO->toArray(), fn ($value) => $value !== null);
        $model->update($data);

        // Refresh model to get updated attributes
        $model->refresh();

        return $this->mapTaskToEntity($model);
    }

    /**
     * Check if user is a member of a project.
     *
     * @param  int  $projectId  The project ID
     * @param  int  $userId  The user ID
     */
    public function isUserMemberOfProject(int $projectId, int $userId): bool
    {
        /** @var ProjectModel|null $project */
        $project = ProjectModel::with('workspace')->find($projectId);

        if (! $project) {
            return false;
        }

        return $this->isUserMemberOfWorkspace($project->workspace_id, $userId);
    }

    /**
     * Add a comment to a task.
     *
     * @param  int  $taskId  The task ID
     * @param  string  $comment  The comment text
     * @param  int  $userId  The user ID
     */
    public function addCommentToTask(int $taskId, string $comment, int $userId): TaskCommentEntity
    {
        /** @var TaskCommentModel $commentModel */
        $commentModel = TaskCommentModel::create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'comment' => $comment,
        ]);

        return $this->mapTaskCommentToEntity($commentModel);
    }

    /**
     * Upload an attachment to a task.
     *
     * @param  int  $taskId  The task ID
     * @param  string  $filePath  The file path
     * @param  string  $fileName  The file name
     * @param  string  $mimeType  The MIME type
     * @param  int  $fileSize  The file size in bytes
     * @param  int  $userId  The user ID
     */
    public function uploadAttachmentToTask(
        int $taskId,
        string $filePath,
        string $fileName,
        string $mimeType,
        int $fileSize,
        int $userId
    ): TaskAttachmentEntity {
        /** @var TaskAttachmentModel $attachmentModel */
        $attachmentModel = TaskAttachmentModel::create([
            'task_id' => $taskId,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $mimeType,
            'file_size' => $fileSize,
            'uploaded_by' => $userId,
        ]);

        return $this->mapTaskAttachmentToEntity($attachmentModel);
    }

    /**
     * Map project model to project entity.
     *
     * @param  ProjectModel  $model  The project model
     */
    private function mapProjectToEntity(ProjectModel $model): ProjectEntity
    {
        return new ProjectEntity(
            $model->id,
            $model->name,
            $model->description,
            $model->workspace_id,
            $model->status,
            $model->created_at,
            $model->updated_at
        );
    }

    /**
     * Map task model to task entity.
     *
     * @param  TaskModel  $model  The task model
     */
    private function mapTaskToEntity(TaskModel $model): TaskEntity
    {
        return new TaskEntity(
            $model->id,
            new TaskTitle($model->title),
            $model->description,
            $model->project_id,
            $model->assigned_to,
            $model->status,
            $model->priority,
            $model->due_date,
            $model->created_at,
            $model->updated_at
        );
    }

    /**
     * Map task comment model to task comment entity.
     *
     * @param  TaskCommentModel  $model  The task comment model
     */
    private function mapTaskCommentToEntity(TaskCommentModel $model): TaskCommentEntity
    {
        return new TaskCommentEntity(
            $model->id,
            $model->task_id,
            $model->user_id,
            $model->comment,
            $model->created_at,
            $model->updated_at
        );
    }

    /**
     * Map task attachment model to task attachment entity.
     *
     * @param  TaskAttachmentModel  $model  The task attachment model
     */
    private function mapTaskAttachmentToEntity(TaskAttachmentModel $model): TaskAttachmentEntity
    {
        return new TaskAttachmentEntity(
            $model->id,
            $model->task_id,
            $model->uploaded_by,
            $model->file_type,
            $model->file_size,
            $model->created_at,
            $model->updated_at,
            new FileName($model->file_name),
            new FilePath($model->file_path)
        );
    }

    /**
     * Get all comments for a task.
     *
     * @param  int  $taskId  The task ID
     * @param  int  $userId  The user ID
     * @return array<int, TaskCommentEntity>
     */
    public function getCommentsByTask(int $taskId, int $userId): array
    {
        /** @var Collection<int, TaskCommentModel> $models */
        $models = TaskCommentModel::where('task_id', $taskId)
            ->with('user')
            ->latest()
            ->get();

        return $models->map(fn (TaskCommentModel $m) => $this->mapTaskCommentToEntity($m))->all();
    }

    /**
     * Update an existing comment (only owner within 30 minutes).
     *
     * @param  int  $commentId  The comment ID
     * @param  string  $newComment  The new comment text
     * @param  int  $userId  The user ID
     */
    public function updateComment(int $commentId, string $newComment, int $userId): TaskCommentEntity
    {
        /** @var TaskCommentModel $model */
        $model = TaskCommentModel::findOrFail($commentId);

        if ($model->user_id !== $userId) {
            throw new InvalidArgumentException(__('workspaces.comment_not_owned'));
        }

        if ($model->created_at->lt(now()->subMinutes(30))) {
            throw new InvalidArgumentException(__('workspaces.comment_edit_expired'));
        }

        $model->update(['comment' => $newComment]);

        // Fresh is guaranteed to return model after findOrFail()
        /** @var TaskCommentModel $freshModel */
        $freshModel = $model->fresh();

        return $this->mapTaskCommentToEntity($freshModel);
    }

    /**
     * Get all attachments for a task.
     *
     * @param  int  $taskId  The task ID
     * @return array<int, TaskAttachmentEntity>
     */
    public function getAttachmentsByTask(int $taskId): array
    {
        /** @var Collection<int, TaskAttachmentModel> $models */
        $models = TaskAttachmentModel::where('task_id', $taskId)->latest()->get();

        return $models->map(fn (TaskAttachmentModel $m) => $this->mapTaskAttachmentToEntity($m))->all();
    }

    /**
     * Delete a task comment (only owner can delete).
     *
     * @param  int  $commentId  The comment ID
     * @param  int  $userId  The user ID
     *
     * @throws InvalidArgumentException if not owned by the user
     */
    public function deleteComment(int $commentId, int $userId): bool
    {
        /** @var TaskCommentModel|null $model */
        $model = TaskCommentModel::find($commentId);

        if (! $model) {
            return false;
        }

        if ($model->user_id !== $userId) {
            throw new InvalidArgumentException(__('workspaces.comment_not_owned'));
        }

        return (bool) $model->delete();
    }

    /**
     * Delete a task attachment (only uploader can delete).
     *
     * @param  int  $attachmentId  The attachment ID
     * @param  int  $userId  The user ID
     */
    public function deleteAttachment(int $attachmentId, int $userId): bool
    {
        /** @var TaskAttachmentModel|null $model */
        $model = TaskAttachmentModel::find($attachmentId);

        if (! $model) {
            return false;
        }

        if ($model->uploaded_by !== $userId) {
            throw new InvalidArgumentException(__('workspaces.attachment_not_owned'));
        }

        if (Storage::exists($model->file_path)) {
            Storage::delete($model->file_path);
        }

        return (bool) $model->delete();
    }

    /**
     * Update an existing project.
     *
     * @param  int  $id  The project ID
     * @param  ProjectDTO  $projectDTO  The project data transfer object
     */
    public function updateProject(int $id, ProjectDTO $projectDTO): ?ProjectEntity
    {
        /** @var ProjectModel|null $model */
        $model = ProjectModel::find($id);

        if (! $model) {
            return null;
        }

        $data = array_filter($projectDTO->toArray(), fn ($value) => $value !== null);
        $model->update($data);
        $model->refresh();

        return $this->mapProjectToEntity($model);
    }

    /**
     * Delete a project by ID.
     *
     * @param  int  $id  The project ID
     */
    public function deleteProject(int $id): bool
    {
        /** @var ProjectModel|null $model */
        $model = ProjectModel::find($id);

        return $model ? (bool) $model->delete() : false;
    }

    /**
     * Get all tasks for a project.
     *
     * @param  int  $projectId  The project ID
     * @return array<int, TaskEntity>
     */
    public function getTasksByProject(int $projectId): array
    {
        /** @var Collection<int, TaskModel> $models */
        $models = TaskModel::where('project_id', $projectId)
            ->with(['assignedUser', 'comments', 'attachments'])
            ->latest()
            ->get();

        return $models->map(fn (TaskModel $m) => $this->mapTaskToEntity($m))->all();
    }

    /**
     * Delete a task by ID.
     *
     * @param  int  $id  The task ID
     */
    public function deleteTask(int $id): bool
    {
        /** @var TaskModel|null $model */
        $model = TaskModel::find($id);

        return $model ? (bool) $model->delete() : false;
    }

    /**
     * Get all members of a workspace.
     *
     * @param  int  $workspaceId  The workspace ID
     * @return array<int, array<string, mixed>>
     */
    public function getWorkspaceMembers(int $workspaceId): array
    {
        /** @var WorkspaceModel|null $workspace */
        $workspace = $this->getModel()->with(['members'])->find($workspaceId);

        if (! $workspace) {
            throw new \InvalidArgumentException(__('workspaces.workspace_not_found', ['id' => $workspaceId]));
        }

        /** @var array<int, array<string, mixed>> $members */
        $members = $workspace->members->map(function (UserModel $member) {
            /**
             * @var WorkspaceMemberPivot $pivot
             *
             * @phpstan-ignore-next-line
             */
            $pivot = $member->pivot;

            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $pivot->role,
                'joined_at' => $pivot->joined_at?->toIso8601String(),
            ];
        })->all();

        return $members;
    }
}
