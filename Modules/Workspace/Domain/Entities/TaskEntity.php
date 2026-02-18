<?php

namespace Modules\Workspace\Domain\Entities;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;

/**
 * Domain entity representing a workspace task.
 * Immutable value object with full encapsulation and business logic.
 */
class TaskEntity
{
    public function __construct(
        private readonly int $id,
        private readonly string $title,
        private readonly ?string $description,
        private readonly int $projectId,
        private readonly ?int $assignedTo,
        private readonly TaskStatus $status,
        private readonly TaskPriority $priority,
        private readonly ?CarbonInterface $dueDate,
        private readonly ?CarbonInterface $createdAt = null,
        private readonly ?CarbonInterface $updatedAt = null
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getAssignedTo(): ?int
    {
        return $this->assignedTo;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    public function getDueDate(): ?CarbonInterface
    {
        return $this->dueDate;
    }

    public function getCreatedAt(): ?CarbonInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?CarbonInterface
    {
        return $this->updatedAt;
    }

    public function isOverdue(): bool
    {
        return $this->dueDate !== null
            && $this->dueDate->isPast()
            && ! $this->status->isCompleted();
    }

    public function markAsCompleted(): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->description,
            $this->projectId,
            $this->assignedTo,
            TaskStatus::COMPLETED,
            $this->priority,
            $this->dueDate,
            $this->createdAt,
            $this->updatedAt
        );
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isAssigned(): bool
    {
        return $this->assignedTo !== null;
    }

    /**
     * Convert entity to array for persistence or serialization.
     *
     * @return array{
     *     id: int,
     *     title: string,
     *     description: string|null,
     *     project_id: int,
     *     assigned_to: int|null,
     *     status: string,
     *     priority: string,
     *     due_date: string|null,
     *     created_at: string|null,
     *     updated_at: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'project_id' => $this->projectId,
            'assigned_to' => $this->assignedTo,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'due_date' => $this->dueDate?->toIso8601String(),
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
