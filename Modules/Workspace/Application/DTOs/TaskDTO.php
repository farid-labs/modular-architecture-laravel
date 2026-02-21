<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * Data Transfer Object for Task operations.
 *
 * Encapsulates task data for transfer between application layers.
 * Supports both snake_case and camelCase array keys for flexibility.
 * Validates task status, priority, and due date during instantiation.
 */
class TaskDTO extends DataTransferObject
{
    /**
     * Task title.
     * Required field, must be between 3-255 characters.
     * Brief summary of the task.
     */
    public string $title;

    /**
     * Optional task description.
     * Can contain detailed information about task requirements.
     */
    public ?string $description = null;

    /**
     * Related project ID.
     * Associates the task with a specific project.
     * Must be a positive integer.
     */
    public int $projectId;

    /**
     * Optional assigned user ID.
     * Tracks who is responsible for completing the task.
     * Null if task is unassigned.
     */
    public ?int $assignedTo = null;

    /**
     * Current task status.
     * Defaults to PENDING if not specified.
     * Valid values: pending, in_progress, completed, blocked, cancelled
     */
    public TaskStatus $status = TaskStatus::PENDING;

    /**
     * Task priority level.
     * Defaults to MEDIUM if not specified.
     * Valid values: low, medium, high, urgent
     */
    public TaskPriority $priority = TaskPriority::MEDIUM;

    /**
     * Optional due date.
     * Target completion date for the task.
     * Cannot be in the past when creating a task.
     */
    public ?CarbonInterface $dueDate = null;

    /**
     * Task creation timestamp.
     * Automatically set when task is created.
     */
    public ?CarbonInterface $createdAt = null;

    /**
     * Task last update timestamp.
     * Updated whenever task data is modified.
     */
    public ?CarbonInterface $updatedAt = null;

    /**
     * Create a TaskDTO instance from an array.
     *
     * Accepts both snake_case and camelCase keys for all fields.
     * Maps status and priority strings to their respective enums.
     * Parses due date and timestamps to Carbon instances if provided.
     *
     * @param  array<string, mixed>  $data  Associative array containing task data
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
     * Transforms camelCase property names to snake_case for database persistence.
     * Converts enum values to their string representations.
     * Formats due date as standard datetime string.
     *
     * @return array<string, mixed> Associative array with snake_case keys
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
