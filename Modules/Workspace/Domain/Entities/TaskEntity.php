<?php

namespace Modules\Workspace\Domain\Entities;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;
use Modules\Workspace\Domain\ValueObjects\TaskTitle;

/**
 * Domain entity representing a workspace task.
 * Immutable value object with full encapsulation and business logic.
 */
class TaskEntity
{
    public function __construct(
        // Unique task identifier
        private readonly int $id,

        // Task title
        private readonly TaskTitle $title,

        // Optional task description
        private readonly ?string $description,

        // Related project ID
        private readonly int $projectId,

        // Optional assigned user ID
        private readonly ?int $assignedTo,

        // Task status
        private readonly TaskStatus $status,

        // Task priority level
        private readonly TaskPriority $priority,

        // Optional due date
        private readonly ?CarbonInterface $dueDate,

        // Task creation timestamp
        private readonly ?CarbonInterface $createdAt = null,

        // Task last update timestamp
        private readonly ?CarbonInterface $updatedAt = null
    ) {}

    // Get task ID
    public function getId(): int
    {
        return $this->id;
    }

    // Get task title
    public function getTitleVO(): TaskTitle
    {
        return $this->title;
    }

    // Get task description
    public function getDescription(): ?string
    {
        return $this->description;
    }

    // Get related project ID
    public function getProjectId(): int
    {
        return $this->projectId;
    }

    // Get assigned user ID
    public function getAssignedTo(): ?int
    {
        return $this->assignedTo;
    }

    // Get task status enum
    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    // Get task priority enum
    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    // Get due date
    public function getDueDate(): ?CarbonInterface
    {
        return $this->dueDate;
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

    // Check if the task is overdue
    public function isOverdue(): bool
    {
        return $this->dueDate !== null
            && $this->dueDate->isPast()
            && ! $this->status->isCompleted();
    }

    // Mark task as completed (Immutable pattern)
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

    // Check if the task is completed
    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    // Check if the task has an assigned user
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
            // Task ID
            'id' => $this->id,

            // Task title
            'title' => $this->title,

            // Task description
            'description' => $this->description,

            // Related project ID
            'project_id' => $this->projectId,

            // Assigned user ID
            'assigned_to' => $this->assignedTo,

            // Task status as string
            'status' => $this->status->value,

            // Task priority as string
            'priority' => $this->priority->value,

            // ISO 8601 formatted due date
            'due_date' => $this->dueDate?->toIso8601String(),

            // ISO 8601 formatted creation timestamp
            'created_at' => $this->createdAt?->toIso8601String(),

            // ISO 8601 formatted update timestamp
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
