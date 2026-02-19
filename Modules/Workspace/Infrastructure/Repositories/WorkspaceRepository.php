<?php

namespace Modules\Workspace\Infrastructure\Repositories;

use InvalidArgumentException;
use Modules\Workspace\Application\DTOs\ProjectDTO;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Entities\WorkspaceEntity;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskAttachmentModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskCommentModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;

/**
 * Workspace repository implementation.
 * Handles data access and mapping between persistence models and domain entities.
 */
class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    /**
     * Create a new repository instance.
     *
     * @param  string  $modelClass  The workspace model class name
     */
    public function __construct(private string $modelClass = WorkspaceModel::class) {}

    /**
     * Get a new workspace model instance.
     */
    private function getModel(): WorkspaceModel
    {
        /** @var WorkspaceModel */
        return new $this->modelClass;
    }

    public function findById(int $id): ?WorkspaceEntity
    {
        $model = $this->getModel()->with(['owner', 'members'])->find($id);

        return $model ? $this->mapToEntity($model) : null;
    }

    public function findBySlug(string $slug): ?WorkspaceEntity
    {
        $model = $this->getModel()->with(['owner', 'members'])->where('slug', $slug)->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function findByOwnerId(int $ownerId): array
    {
        $models = $this->getModel()->with(['members'])->where('owner_id', $ownerId)->get();

        return $models->map(fn ($m) => $this->mapToEntity($m))->toArray();
    }

    public function create(WorkspaceDTO $workspaceDTO): WorkspaceEntity
    {
        $data = $workspaceDTO->toArray();
        $model = $this->getModel()->create($data);

        // Attach owner as a member with 'owner' role
        $model->members()->attach($data['owner_id'], [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return $this->mapToEntity($model);
    }

    public function update(int $id, WorkspaceDTO $workspaceDTO): ?WorkspaceEntity
    {
        $model = $this->getModel()->find($id);
        if (! $model) {
            return null;
        }

        $data = array_filter($workspaceDTO->toArray(), fn ($value) => $value !== null);
        $model->update($data);

        return $this->mapToEntity($model);
    }

    public function delete(int $id): bool
    {
        $model = $this->getModel()->find($id);

        return $model ? (bool) $model->delete() : false;
    }

    public function getAll(): array
    {
        $models = $this->getModel()->with(['owner'])->get();

        return $models->map(fn ($m) => $this->mapToEntity($m))->toArray();
    }

    public function getWorkspacesByUser(int $userId): array
    {
        $models = $this->getModel()->with(['members', 'owner'])
            ->withCount(['members', 'projects'])
            ->where(function ($query) use ($userId) {
                $query->where('owner_id', $userId)
                    ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
            })
            ->get();

        return $models->map(fn ($m) => $this->mapToEntity($m))->toArray();
    }

    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool
    {
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

    public function removeUserFromWorkspace(int $workspaceId, int $userId): int
    {
        $model = $this->getModel()->find($workspaceId);

        if (! $model) {
            return 0;
        }

        return $model->members()->detach($userId);
    }

    /**
     * Map workspace model to workspace entity.
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

    public function findProjectById(int $id): ?ProjectEntity
    {
        $model = ProjectModel::with(['workspace'])->find($id);

        return $model ? $this->mapProjectToEntity($model) : null;
    }

    public function getProjectsByWorkspace(int $workspaceId): array
    {
        $models = ProjectModel::where('workspace_id', $workspaceId)->get();

        return $models->map(fn ($model) => $this->mapProjectToEntity($model))->all();
    }

    public function createProject(ProjectDTO $projectDTO): ProjectEntity
    {
        $model = ProjectModel::create($projectDTO->toArray());

        return $this->mapProjectToEntity($model);
    }

    /**
     * Check if workspace exists by ID
     */
    public function workspaceExists(int $workspaceId): bool
    {
        return $this->getModel()->where('id', $workspaceId)->exists();
    }

    /**
     * Check if user is a member of an existing workspace
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

    public function findTaskById(int $id): ?TaskEntity
    {
        // Use correct relationship name 'project' instead of 'projectModel'
        $model = TaskModel::with(['project', 'assignedUser', 'comments', 'attachments'])
            ->find($id);

        return $model ? $this->mapTaskToEntity($model) : null;
    }

    public function createTask(TaskDTO $taskDTO): TaskEntity
    {
        $model = TaskModel::create($taskDTO->toArray());

        return $this->mapTaskToEntity($model);
    }

    public function updateTask(int $id, TaskDTO $taskDTO): ?TaskEntity
    {
        // Use query builder properly to ensure model instance
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

    public function isUserMemberOfProject(int $projectId, int $userId): bool
    {
        $project = ProjectModel::with('workspace')->find($projectId);
        if (! $project) {
            return false;
        }

        return $this->isUserMemberOfWorkspace($project->workspace_id, $userId);
    }

    public function addCommentToTask(int $taskId, string $comment, int $userId): TaskCommentEntity
    {
        $commentModel = TaskCommentModel::create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'comment' => $comment,
        ]);

        // Return mapped entity instead of void
        return $this->mapTaskCommentToEntity($commentModel);
    }

    public function uploadAttachmentToTask(
        int $taskId,
        string $filePath,
        string $fileName,
        string $mimeType,
        int $fileSize,
        int $userId
    ): TaskAttachmentEntity {
        $attachmentModel = TaskAttachmentModel::create([
            'task_id' => $taskId,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $mimeType,
            'file_size' => $fileSize,
            'uploaded_by' => $userId,
        ]);

        // Return mapped entity instead of void
        return $this->mapTaskAttachmentToEntity($attachmentModel);
    }

    /**
     * Map project model to project entity.
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
     */
    private function mapTaskToEntity(TaskModel $model): TaskEntity
    {
        return new TaskEntity(
            $model->id,
            $model->title,
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
     * Now properly used in addCommentToTask method
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
     * Now properly used in uploadAttachmentToTask method
     */
    private function mapTaskAttachmentToEntity(TaskAttachmentModel $model): TaskAttachmentEntity
    {
        return new TaskAttachmentEntity(
            $model->id,
            $model->task_id,
            $model->uploaded_by,
            $model->file_path,
            $model->file_name,
            $model->file_type,
            $model->file_size,
            $model->created_at,
            $model->updated_at
        );
    }
}
