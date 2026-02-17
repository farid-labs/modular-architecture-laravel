<?php

namespace Modules\Workspace\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workspace\Domain\Entities\WorkspaceEntity;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;

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
