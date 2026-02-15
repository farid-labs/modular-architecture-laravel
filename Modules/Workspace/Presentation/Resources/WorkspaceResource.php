<?php

namespace Modules\Workspace\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;

/**
 * @mixin WorkspaceModel
 */
class WorkspaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            'members_count' => $this->resource->members?->count() ?? 0,
            'projects_count' => $this->resource->projects?->count() ?? 0,
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
