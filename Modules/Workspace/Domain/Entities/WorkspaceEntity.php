<?php

namespace Modules\Workspace\Domain\Entities;

use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Domain\ValueObjects\WorkspaceName;

class WorkspaceEntity
{
    private int $id;

    private WorkspaceName $name;

    private string $slug;

    private ?string $description;

    private WorkspaceStatus $status;

    private int $ownerId;

    public function __construct(
        int $id,
        WorkspaceName $name,
        string $slug,
        ?string $description,
        WorkspaceStatus $status,
        int $ownerId
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->status = $status;
        $this->ownerId = $ownerId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name->getValue();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function updateName(WorkspaceName $newName): void
    {
        $this->name = $newName;
        $this->slug = $newName->getSlug();
    }

    public function getStatus(): WorkspaceStatus
    {
        return $this->status;
    }

    public function isOwner(int $userId): bool
    {
        return $this->ownerId === $userId;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     description: string|null,
     *     status: string,
     *     owner_id: int
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name->getValue(),
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status->value,
            'owner_id' => $this->ownerId,
        ];
    }
}
