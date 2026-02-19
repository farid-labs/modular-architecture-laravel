<?php

namespace Modules\Workspace\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workspace\Domain\Entities\TaskEntity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskResource',
    type: 'object',
    description: 'Task resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Implement login feature'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'project_id', type: 'integer', example: 1),
        new OA\Property(property: 'assigned_to', type: 'integer', nullable: true, example: 2),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['pending', 'in_progress', 'completed', 'blocked', 'cancelled'],
            example: 'pending'
        ),
        new OA\Property(
            property: 'priority',
            type: 'string',
            enum: ['low', 'medium', 'high', 'urgent'],
            example: 'high'
        ),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
        new OA\Property(property: 'is_completed', type: 'boolean', example: false),
        new OA\Property(property: 'is_assigned', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array for API responses.
     * Converts the TaskEntity into a JSON-friendly format.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Fallback to default array transformation if resource is not a TaskEntity
        if (! $this->resource instanceof TaskEntity) {
            /** @var array<string, mixed> */
            return parent::toArray($request);
        }

        // Map TaskEntity properties to array keys
        return [
            'id' => $this->resource->getId(),
            'title' => $this->resource->getTitle(),
            'description' => $this->resource->getDescription(),
            'project_id' => $this->resource->getProjectId(),
            'assigned_to' => $this->resource->getAssignedTo(),
            'status' => $this->resource->getStatus()->value,
            'priority' => $this->resource->getPriority()->value,
            'due_date' => $this->resource->getDueDate()?->toIso8601String(),
            'is_overdue' => $this->resource->isOverdue(),
            'is_completed' => $this->resource->isCompleted(),
            'is_assigned' => $this->resource->isAssigned(),
            'created_at' => $this->resource->getCreatedAt()?->toIso8601String(),
            'updated_at' => $this->resource->getUpdatedAt()?->toIso8601String(),
        ];
    }
}
