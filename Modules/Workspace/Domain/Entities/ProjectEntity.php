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
        // Unique project identifier
        private readonly int $id,

        // Project name
        private readonly string $name,

        // Optional project description
        private readonly ?string $description,

        // Related workspace ID
        private readonly int $workspaceId,

        // Current project status
        private readonly ProjectStatus $status,

        // Project creation timestamp
        private readonly ?CarbonInterface $createdAt = null,

        // Project last update timestamp
        private readonly ?CarbonInterface $updatedAt = null
    ) {}

    // Get project ID
    public function getId(): int
    {
        return $this->id;
    }

    // Get project name
    public function getName(): string
    {
        return $this->name;
    }

    // Get project description
    public function getDescription(): ?string
    {
        return $this->description;
    }

    // Get related workspace ID
    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }

    // Get project status enum
    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    // Get creation timestamp
    public function getCreatedAt(): ?CarbonInterface
    {
        return $this->createdAt;
    }

    // Get last update timestamp
    public function getUpdatedAt(): ?CarbonInterface
    {
        return $this->updatedAt;
    }

    // Check if project is active
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    // Check if project is completed
    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    // Check if project is archived
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
            // Project ID
            'id' => $this->id,

            // Project name
            'name' => $this->name,

            // Project description
            'description' => $this->description,

            // Related workspace ID
            'workspace_id' => $this->workspaceId,

            // Status as string value
            'status' => $this->status->value,

            // ISO 8601 formatted creation timestamp
            'created_at' => $this->createdAt?->toIso8601String(),

            // ISO 8601 formatted update timestamp
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
