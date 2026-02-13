<?php

namespace Modules\Workspace\Domain\Repositories;

use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Infrastructure\Persistence\Models\Workspace;

interface WorkspaceRepositoryInterface
{
    public function findById(int $id): ?Workspace;

    public function findBySlug(string $slug): ?Workspace;

    /**
     * @return array<int, Workspace>
     */
    public function findByOwnerId(int $ownerId): array;

    public function create(WorkspaceDTO $workspaceDTO): Workspace;

    public function update(int $id, WorkspaceDTO $workspaceDTO): ?Workspace;

    public function delete(int $id): bool;

    /**
     * @return array<int, Workspace>
     */
    public function getAll(): array;

    /**
     * @return array<int, Workspace>
     */
    public function getWorkspacesByUser(int $userId): array;

    public function addUserToWorkspace(int $workspaceId, int $userId, string $role): bool;

    public function removeUserFromWorkspace(int $workspaceId, int $userId): bool;
}
