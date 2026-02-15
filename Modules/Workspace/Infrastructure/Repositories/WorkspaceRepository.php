<?php

namespace Modules\Workspace\Infrastructure\Repositories;

use Modules\Workspace\Application\DTOs\ProjectDTO;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Entities\WorkspaceEntity;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Modules\Workspace\Domain\ValueObjects\WorkspaceName;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskAttachmentModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskCommentModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;

class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    public function __construct(private WorkspaceModel $model) {}

    public function findById(int $id): ?WorkspaceEntity
    {
        $model = $this->model->with(['owner', 'members'])->find($id);

        return $model ? $this->mapToEntity($model) : null;
    }

    public function findBySlug(string $slug): ?WorkspaceEntity
    {
        $model = $this->model->with(['owner', 'members'])->where('slug', $slug)->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function findByOwnerId(int $ownerId): array
    {
        $models = $this->model->with(['members'])->where('owner_id', $ownerId)->get();

        return $models->map(fn ($m) => $this->mapToEntity($m))->toArray();
    }

    public function create(WorkspaceDTO $workspaceDTO): WorkspaceEntity
    {
        $model = $this->model->create($workspaceDTO->toArray());

        return $this->mapToEntity($model);
    }

    public function update(int $id, WorkspaceDTO $workspaceDTO): ?WorkspaceEntity
    {
        $model = $this->model->find($id);
        if (! $model) {
            return null;
        }

        $model->update($workspaceDTO->toArray());

        return $this->mapToEntity($model);
    }

    public function delete(int $id): bool
    {
        $model = $this->model->find($id);

        return $model ? (bool) $model->delete() : false;
    }

    public function getAll(): array
    {
        $models = $this->model->with(['owner'])->get();

        return $models->map(fn ($m) => $this->mapToEntity($m))->toArray();
    }

    public function getWorkspacesByUser(int $userId): array
    {
        $models = $this->model->with(['members'])
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->get();

        return $models->map(fn ($m) => $this->mapToEntity($m))->toArray();
    }

    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool
    {
        $model = $this->model->find($workspaceId);
        if (! $model) {
            return false;
        }

        $model->members()->attach($userId, ['role' => $role, 'joined_at' => now()]);

        return true;
    }

    public function removeUserFromWorkspace(int $workspaceId, int $userId): bool
    {
        $model = $this->model->find($workspaceId);
        if (! $model) {
            return false;
        }

        $model->members()->detach($userId);

        return true;
    }

    private function mapToEntity(WorkspaceModel $model): WorkspaceEntity
    {
        return new WorkspaceEntity(
            $model->id,
            new WorkspaceName($model->name),
            $model->slug,
            $model->description,
            $model->status,
            $model->owner_id
        );
    }

    public function findProjectById(int $id): ?ProjectEntity
    {
        $model = ProjectModel::with(['workspace'])->find($id);

        return $model ? $this->mapProjectToEntity($model) : null;
    }

    public function createProject(ProjectDTO $projectDTO): ProjectEntity
    {
        $model = ProjectModel::create($projectDTO->toArray());

        return $this->mapProjectToEntity($model);
    }

    public function isUserMemberOfWorkspace(int $workspaceId, int $userId): bool
    {
        return WorkspaceModel::where('id', $workspaceId)
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->exists();
    }

    public function findTaskById(int $id): ?TaskEntity
    {
        // 🔑 فیکس کلیدی: تغییر 'project' به 'projectModel' (مطابق نام متد در TaskModel)
        $model = TaskModel::with(['projectModel', 'assignedUser', 'comments', 'attachments'])
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
        $model = TaskModel::find($id);
        if (! $model) {
            return null;
        }
        $model->update($taskDTO->toArray());

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

    public function addCommentToTask(int $taskId, string $comment, int $userId): void
    {
        TaskCommentModel::create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'comment' => $comment,
        ]);
    }

    public function uploadAttachmentToTask(
        int $taskId,
        string $filePath,
        string $fileName,
        string $mimeType,
        int $fileSize,
        int $userId
    ): void {
        TaskAttachmentModel::create([
            'task_id' => $taskId,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $mimeType,
            'file_size' => $fileSize,
            'uploaded_by' => $userId,
        ]);
    }

    private function mapProjectToEntity(ProjectModel $model): ProjectEntity
    {
        return new ProjectEntity(
            $model->id,
            $model->name,
            $model->description,
            $model->workspace_id,
            $model->status
        );
    }

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
            $model->due_date
        );
    }
}
