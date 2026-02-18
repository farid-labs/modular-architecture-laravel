<?php

namespace Modules\Workspace\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskAttachmentResource',
    type: 'object',
    description: 'Task attachment resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'task_id', type: 'integer', example: 10),
        new OA\Property(property: 'file_name', type: 'string', example: 'document.pdf'),
        new OA\Property(property: 'file_path', type: 'string', example: 'attachments/document.pdf'),
        new OA\Property(property: 'file_type', type: 'string', example: 'application/pdf'),
        new OA\Property(property: 'file_size', type: 'integer', example: 102400),
        new OA\Property(property: 'uploaded_by', type: 'integer', example: 5),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class TaskAttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof TaskAttachmentEntity) {
            /** @var array<string, mixed> */
            return parent::toArray($request);
        }

        return [
            'id' => $this->resource->getId(),
            'task_id' => $this->resource->getTaskId(),
            'file_name' => $this->resource->getFileName(),
            'file_path' => $this->resource->getFilePath(),
            'file_type' => $this->resource->getMimeType(),
            'file_size' => $this->resource->getFileSize(),
            'uploaded_by' => $this->resource->getUserId(),
            'created_at' => $this->resource->getCreatedAt()?->toIso8601String(),
            'updated_at' => $this->resource->getUpdatedAt()?->toIso8601String(),
        ];
    }
}
