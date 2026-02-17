<?php

namespace Modules\Workspace\Domain\Entities;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;

class WorkspaceEntity
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $slug,
        private readonly ?string $description,
        private readonly WorkspaceStatus $status,
        private readonly int $ownerId,
        private readonly CarbonInterface $createdAt,
        private readonly CarbonInterface $updatedAt,
        private readonly int $membersCount = 0,
        private readonly int $projectsCount = 0
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): WorkspaceStatus
    {
        return $this->status;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getMembersCount(): int
    {
        return $this->membersCount;
    }

    public function getProjectsCount(): int
    {
        return $this->projectsCount;
    }

    public function getCreatedAt(): CarbonInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): CarbonInterface
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status === WorkspaceStatus::ACTIVE;
    }

    /**
     * ✅ الگوی Immutable: به‌جای تغییر اینستنس، یک اینستنس جدید برمی‌گرداند
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
            now(), // ✅ updatedAt به‌روز می‌شود
            $this->membersCount,
            $this->projectsCount
        );
    }

    /**
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
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status->value,
            'owner_id' => $this->ownerId,
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
            'members_count' => $this->membersCount,
            'projects_count' => $this->projectsCount,
        ];
    }
}
