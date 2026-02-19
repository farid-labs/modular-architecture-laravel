<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;
use Spatie\DataTransferObject\DataTransferObject;

class TaskDTO extends DataTransferObject
{
    // Task title
    public string $title;

    // Optional task description
    public ?string $description = null;

    // Related project ID
    public int $projectId;

    // Optional assigned user ID
    public ?int $assignedTo = null;

    // Current task status (default: PENDING)
    public TaskStatus $status = TaskStatus::PENDING;

    // Task priority level (default: MEDIUM)
    public TaskPriority $priority = TaskPriority::MEDIUM;

    // Optional due date
    public ?CarbonInterface $dueDate = null;

    // Task creation timestamp
    public ?CarbonInterface $createdAt = null;

    // Task last update timestamp
    public ?CarbonInterface $updatedAt = null;

    /**
     * Create a TaskDTO instance from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self([
            // Required task title
            'title' => $data['title'],

            // Optional description
            'description' => $data['description'] ?? null,

            // Related project ID
            'projectId' => $data['project_id'],

            // Optional assigned user ID
            'assignedTo' => $data['assigned_to'] ?? null,

            // Map status if provided, otherwise default to PENDING
            'status' => isset($data['status'])
                ? TaskStatus::from($data['status'])
                : TaskStatus::PENDING,

            // Map priority if provided, otherwise default to MEDIUM
            'priority' => isset($data['priority'])
                ? TaskPriority::from($data['priority'])
                : TaskPriority::MEDIUM,

            // Parse due date if provided
            'dueDate' => isset($data['due_date'])
                ? \Carbon\Carbon::parse($data['due_date'])
                : null,

            // Parse creation timestamp if provided
            'createdAt' => isset($data['created_at'])
                ? \Carbon\Carbon::parse($data['created_at'])
                : null,

            // Parse update timestamp if provided
            'updatedAt' => isset($data['updated_at'])
                ? \Carbon\Carbon::parse($data['updated_at'])
                : null,
        ]);
    }

    /**
     * Convert the DTO to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            // Task title
            'title' => $this->title,

            // Task description
            'description' => $this->description,

            // Project ID in snake_case format
            'project_id' => $this->projectId,

            // Assigned user ID
            'assigned_to' => $this->assignedTo,

            // Enum value of the task status
            'status' => $this->status->value,

            // Enum value of the task priority
            'priority' => $this->priority->value,

            // Due date formatted as standard datetime string
            'due_date' => $this->dueDate?->toDateTimeString(),
        ];
    }
}
