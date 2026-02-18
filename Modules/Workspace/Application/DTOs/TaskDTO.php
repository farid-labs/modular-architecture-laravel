<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;
use Spatie\DataTransferObject\DataTransferObject;

class TaskDTO extends DataTransferObject
{
    public string $title;

    public ?string $description = null;

    public int $projectId;

    public ?int $assignedTo = null;

    public TaskStatus $status = TaskStatus::PENDING;

    public TaskPriority $priority = TaskPriority::MEDIUM;

    public ?CarbonInterface $dueDate = null;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'projectId' => $data['project_id'],
            'assignedTo' => $data['assigned_to'] ?? null,
            'status' => isset($data['status'])
                ? TaskStatus::from($data['status'])
                : TaskStatus::PENDING,
            'priority' => isset($data['priority'])
                ? TaskPriority::from($data['priority'])
                : TaskPriority::MEDIUM,
            'dueDate' => isset($data['due_date'])
                ? \Carbon\Carbon::parse($data['due_date'])
                : null,
            'createdAt' => isset($data['created_at'])
                ? \Carbon\Carbon::parse($data['created_at'])
                : null,
            'updatedAt' => isset($data['updated_at'])
                ? \Carbon\Carbon::parse($data['updated_at'])
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'project_id' => $this->projectId,
            'assigned_to' => $this->assignedTo,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'due_date' => $this->dueDate?->toDateTimeString(),
        ];
    }
}
