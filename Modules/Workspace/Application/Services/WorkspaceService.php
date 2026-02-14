<?php

namespace Modules\Workspace\Application\Services;

use Illuminate\Support\Facades\Log;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Modules\Workspace\Infrastructure\Persistence\Models\Workspace;

class WorkspaceService
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository
    ) {}

    public function createWorkspace(WorkspaceDTO $workspaceDTO, UserModel $owner): Workspace
    {
        Log::channel('domain')->info('Creating workspace', [
            'name' => $workspaceDTO->name,
            'owner_id' => $owner->id,
        ]);

        // Set owner ID
        $workspaceDTO->owner_id = $owner->id;

        $workspace = $this->workspaceRepository->create($workspaceDTO);

        // Add owner as member
        $this->workspaceRepository->addUserToWorkspace($workspace->id, $owner->id, 'owner');

        Log::channel('domain')->info('Workspace created successfully', [
            'workspace_id' => $workspace->id,
            'slug' => $workspace->slug,
        ]);

        return $workspace;
    }

    public function getWorkspaceById(int $id): Workspace
    {
        $workspace = $this->workspaceRepository->findById($id);

        if (! $workspace) {
            throw new \InvalidArgumentException('Workspace not found');
        }

        return $workspace;
    }

    public function getWorkspaceBySlug(string $slug): Workspace
    {
        $workspace = $this->workspaceRepository->findBySlug($slug);

        if (! $workspace) {
            throw new \InvalidArgumentException('Workspace not found');
        }

        return $workspace;
    }

    public function updateWorkspace(int $id, WorkspaceDTO $workspaceDTO): Workspace
    {
        Log::channel('domain')->info('Updating workspace', [
            'workspace_id' => $id,
            'name' => $workspaceDTO->name,
        ]);

        $workspace = $this->workspaceRepository->update($id, $workspaceDTO);

        if (! $workspace) {
            throw new \InvalidArgumentException('Workspace not found');
        }

        Log::channel('domain')->info('Workspace updated successfully', [
            'workspace_id' => $id,
        ]);

        return $workspace;
    }

    public function deleteWorkspace(int $id): bool
    {
        Log::channel('domain')->info('Deleting workspace', ['workspace_id' => $id]);

        return $this->workspaceRepository->delete($id);
    }

    /**
     * @return array<int, Workspace>
     */
    public function getWorkspacesByUser(int $userId): array
    {
        return $this->workspaceRepository->getWorkspacesByUser($userId);
    }

    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool
    {
        Log::channel('domain')->info('Adding user to workspace', [
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'role' => $role,
        ]);

        return $this->workspaceRepository->addUserToWorkspace($workspaceId, $userId, $role);
    }

    public function removeUserFromWorkspace(int $workspaceId, int $userId): bool
    {
        Log::channel('domain')->info('Removing user from workspace', [
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
        ]);

        return $this->workspaceRepository->removeUserFromWorkspace($workspaceId, $userId);
    }
}
