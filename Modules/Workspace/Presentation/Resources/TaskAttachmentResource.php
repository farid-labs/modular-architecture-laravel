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
     * Transform the resource into an array for API responses.
     * Converts the TaskAttachmentEntity into a JSON-friendly format.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Fallback to default array transformation if resource is not a TaskAttachmentEntity
        if (! $this->resource instanceof TaskAttachmentEntity) {
            /** @var array<string, mixed> */
            return parent::toArray($request);
        }

        // Map TaskAttachmentEntity properties to array keys
        return [
            // Unique attachment identifier
            'id' => $this->resource->getId(),

            // Associated task ID
            'task_id' => $this->resource->getTaskId(),

            // Original file name (extracted from FileName value object)
            'file_name' => $this->resource->getFileNameVO()->value(),

            // Storage path (extracted from FilePath value object)
            'file_path' => $this->resource->getFilePathVO()->value(),

            // File MIME type
            'file_type' => $this->resource->getMimeType(),

            // File size in bytes
            'file_size' => $this->resource->getFileSize(),

            // User ID who uploaded the attachment
            'uploaded_by' => $this->resource->getUploadedBy(),

            // ISO 8601 formatted creation timestamp
            'created_at' => $this->resource->getCreatedAt()?->toIso8601String(),

            // ISO 8601 formatted last update timestamp
            'updated_at' => $this->resource->getUpdatedAt()?->toIso8601String(),
        ];
    }
}
