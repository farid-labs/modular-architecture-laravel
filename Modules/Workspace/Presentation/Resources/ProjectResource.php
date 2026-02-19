<?php

namespace Modules\Workspace\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workspace\Domain\Entities\ProjectEntity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProjectResource',
    type: 'object',
    description: 'Project resource representation',
    required: ['id', 'name', 'workspace_id', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Website Redesign'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Redesign company website'),
        new OA\Property(property: 'workspace_id', type: 'integer', example: 10),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['active', 'completed', 'archived'],
            example: 'active'
        ),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-17T14:18:47+00:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-17T14:18:47+00:00'),
    ]
)]
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array for API responses.
     * Converts the ProjectEntity into a JSON-friendly format.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Fallback to default array transformation if resource is not a ProjectEntity
        if (! $this->resource instanceof ProjectEntity) {
            /** @var array<string, mixed> */
            return parent::toArray($request);
        }

        // Map ProjectEntity properties to array keys
        return [
            'id' => $this->resource->getId(),
            'name' => $this->resource->getName(),
            'description' => $this->resource->getDescription(),
            'workspace_id' => $this->resource->getWorkspaceId(),
            'status' => $this->resource->getStatus()->value,
            'is_active' => $this->resource->isActive(),
            'created_at' => $this->resource->getCreatedAt()?->toIso8601String(),
            'updated_at' => $this->resource->getUpdatedAt()?->toIso8601String(),
        ];
    }
}
