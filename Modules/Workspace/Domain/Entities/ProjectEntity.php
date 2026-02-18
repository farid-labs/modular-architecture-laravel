<?php

namespace Modules\Workspace\Domain\Entities;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\Enums\ProjectStatus;

/**
 * Domain entity representing a workspace project.
 * Immutable value object with full encapsulation and business logic.
 */
class ProjectEntity
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly ?string $description,
        private readonly int $workspaceId,
        private readonly ProjectStatus $status,
        private readonly ?CarbonInterface $createdAt = null,
        private readonly ?CarbonInterface $updatedAt = null
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): ?CarbonInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?CarbonInterface
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isArchived(): bool
    {
        return $this->status->isArchived();
    }

    /**
     * Convert entity to array for persistence or serialization.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     workspace_id: int,
     *     status: string,
     *     created_at: string|null,
     *     updated_at: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'workspace_id' => $this->workspaceId,
            'status' => $this->status->value,
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
