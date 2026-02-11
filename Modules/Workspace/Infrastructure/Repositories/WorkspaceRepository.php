<?php

namespace Modules\Workspace\Infrastructure\Repositories;

use Modules\Users\Infrastructure\Persistence\Models\User;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Infrastructure\Persistence\Models\Workspace;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    public function __construct(private Workspace $model) {}

    public function findById(int $id): ?Workspace
    {
        return $this->model->with(['owner', 'members'])->find($id);
    }

    public function findBySlug(string $slug): ?Workspace
    {
        return $this->model->with(['owner', 'members'])->where('slug', $slug)->first();
    }

    public function findByOwnerId(int $ownerId): array
    {
        return $this->model->with(['members'])
            ->where('owner_id', $ownerId)
            ->get()
            ->toArray();
    }

    public function create(WorkspaceDTO $workspaceDTO): Workspace
    {
        return $this->model->create($workspaceDTO->toArray());
    }

    public function update(int $id, WorkspaceDTO $workspaceDTO): ?Workspace
    {
        $workspace = $this->findById($id);

        if (! $workspace) {
            return null;
        }

        $workspace->update($workspaceDTO->toArray());

        return $workspace;
    }

    public function delete(int $id): bool
    {
        $workspace = $this->findById($id);

        if (! $workspace) {
            return false;
        }

        return (bool) $workspace->delete();
    }

    public function getAll(): array
    {
        return $this->model->with(['owner'])->get()->toArray();
    }

    public function getWorkspacesByUser(int $userId): array
    {
        return $this->model->with(['members'])
            ->whereHas('members', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get()
            ->toArray();
    }

    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool
    {
        $workspace = $this->findById($workspaceId);

        if (! $workspace) {
            return false;
        }

        $workspace->members()->attach($userId, [
            'role' => $role,
            'joined_at' => now(),
        ]);

        return true;
    }

    public function removeUserFromWorkspace(int $workspaceId, int $userId): bool
    {
        $workspace = $this->findById($workspaceId);

        if (! $workspace) {
            return false;
        }

        $workspace->members()->detach($userId);

        return true;
    }
}
