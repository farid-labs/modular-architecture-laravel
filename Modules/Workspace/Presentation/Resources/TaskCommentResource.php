<?php

namespace Modules\Workspace\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskCommentResource',
    type: 'object',
    description: 'Task comment resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'task_id', type: 'integer', example: 10),
        new OA\Property(property: 'user_id', type: 'integer', example: 5),
        new OA\Property(property: 'comment', type: 'string', example: 'Great work!'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class TaskCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof TaskCommentEntity) {
            /** @var array<string, mixed> */
            return parent::toArray($request);
        }

        return [
            'id' => $this->resource->getId(),
            'task_id' => $this->resource->getTaskId(),
            'user_id' => $this->resource->getUserId(),
            'comment' => $this->resource->getComment(),
            'created_at' => $this->resource->getCreatedAt()?->toIso8601String(),
            'updated_at' => $this->resource->getUpdatedAt()?->toIso8601String(),
        ];
    }
}
