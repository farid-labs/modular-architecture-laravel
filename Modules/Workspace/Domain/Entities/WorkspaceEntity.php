<?php

namespace Modules\Workspace\Domain\Entities;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;

/**
 * Domain entity representing a workspace.
 * Immutable value object with full encapsulation and business logic.
 */
class WorkspaceEntity
{
    public function __construct(
        // Unique workspace ID
        private readonly int $id,

        // Workspace name
        private readonly string $name,

        // Workspace slug (URL-friendly)
        private readonly string $slug,

        // Optional description
        private readonly ?string $description,

        // Workspace status enum
        private readonly WorkspaceStatus $status,

        // Owner user ID
        private readonly int $ownerId,

        // Creation timestamp
        private readonly CarbonInterface $createdAt,

        // Last update timestamp
        private readonly CarbonInterface $updatedAt,

        // Number of workspace members
        private readonly int $membersCount = 0,

        // Number of projects in workspace
        private readonly int $projectsCount = 0
    ) {}

    // Get workspace ID
    public function getId(): int
    {
        return $this->id;
    }

    // Get workspace name
    public function getName(): string
    {
        return $this->name;
    }

    // Get workspace slug
    public function getSlug(): string
    {
        return $this->slug;
    }

    // Get workspace description
    public function getDescription(): ?string
    {
        return $this->description;
    }

    // Get workspace status
    public function getStatus(): WorkspaceStatus
    {
        return $this->status;
    }

    // Get owner user ID
    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    // Get member count
    public function getMembersCount(): int
    {
        return $this->membersCount;
    }

    // Get project count
    public function getProjectsCount(): int
    {
        return $this->projectsCount;
    }

    // Get creation timestamp
    public function getCreatedAt(): CarbonInterface
    {
        return $this->createdAt;
    }

    // Get last update timestamp
    public function getUpdatedAt(): CarbonInterface
    {
        return $this->updatedAt;
    }

    // Check if workspace is active
    public function isActive(): bool
    {
        return $this->status === WorkspaceStatus::ACTIVE;
    }

    /**
     * Immutable pattern: return a new instance with updated name.
     * Updated 'slug' is generated from new name.
     * 'updatedAt' timestamp is refreshed.
     */
    public function withName(string $newName): self
    {
        return new self(
            $this->id,
            $newName,
            Str::slug($newName),
            $this->description,
            $this->status,
            $this->ownerId,
            $this->createdAt,
            now(), // refresh updatedAt
            $this->membersCount,
            $this->projectsCount
        );
    }

    /**
     * Convert entity to array for persistence or serialization.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     description: string|null,
     *     status: string,
     *     owner_id: int,
     *     created_at: string,
     *     updated_at: string,
     *     members_count: int,
     *     projects_count: int
     * }
     */
    public function toArray(): array
    {
        return [
            // Workspace ID
            'id' => $this->id,

            // Workspace name
            'name' => $this->name,

            // Slug for URL
            'slug' => $this->slug,

            // Optional description
            'description' => $this->description,

            // Status as string
            'status' => $this->status->value,

            // Owner user ID
            'owner_id' => $this->ownerId,

            // ISO 8601 formatted creation timestamp
            'created_at' => $this->createdAt->toIso8601String(),

            // ISO 8601 formatted update timestamp
            'updated_at' => $this->updatedAt->toIso8601String(),

            // Number of members
            'members_count' => $this->membersCount,

            // Number of projects
            'projects_count' => $this->projectsCount,
        ];
    }
}
