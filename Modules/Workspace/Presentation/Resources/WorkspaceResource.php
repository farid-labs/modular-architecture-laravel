<?php

namespace Modules\Workspace\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workspace\Domain\Entities\WorkspaceEntity;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WorkspaceResource',
    type: 'object',
    description: 'Workspace resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Marketing Team'),
        new OA\Property(property: 'slug', type: 'string', example: 'marketing-team'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['active', 'inactive', 'suspended'],
            example: 'active'
        ),
        new OA\Property(
            property: 'owner',
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
            ]
        ),
        new OA\Property(property: 'members_count', type: 'integer', example: 5),
        new OA\Property(property: 'projects_count', type: 'integer', example: 10),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class WorkspaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof WorkspaceEntity) {
            return [
                'id' => $this->resource->getId(),
                'name' => $this->resource->getName(),
                'slug' => $this->resource->getSlug(),
                'description' => $this->resource->getDescription(),
                'status' => $this->resource->getStatus()->value,
                'owner' => ['id' => $this->resource->getOwnerId()],
                'members_count' => $this->resource->getMembersCount(),
                'projects_count' => $this->resource->getProjectsCount(),
                'created_at' => $this->resource->getCreatedAt()->toIso8601String(),
                'updated_at' => $this->resource->getUpdatedAt()->toIso8601String(),
            ];
        }

        if ($this->resource instanceof WorkspaceModel) {
            return [
                'id' => $this->resource->id,
                'name' => $this->resource->name,
                'slug' => $this->resource->slug,
                'description' => $this->resource->description,
                'status' => $this->resource->status->value,
                'owner' => $this->resource->owner ? [
                    'id' => $this->resource->owner->id,
                    'name' => $this->resource->owner->name,
                    'email' => $this->resource->owner->email,
                ] : null,
                'members_count' => $this->resource->members_count,
                'projects_count' => $this->resource->projects_count,
                'created_at' => $this->resource->created_at?->toIso8601String(),
                'updated_at' => $this->resource->updated_at?->toIso8601String(),
            ];
        }

        return [];
    }
}
