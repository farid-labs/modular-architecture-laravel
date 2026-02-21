<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * Data Transfer Object for Task Attachment.
 * Handles input/output mapping for attachment operations.
 */
class TaskAttachmentDTO extends DataTransferObject
{
    public int $taskId;

    public int $userId;

    public string $fileName;

    public string $filePath;

    public string $mimeType;

    public int $fileSize;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;

    /**
     * Create DTO from array (supports both snake_case and camelCase).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self([
            'taskId' => (int) ($data['task_id'] ?? $data['taskId']),
            'userId' => (int) ($data['user_id'] ?? $data['userId'] ?? $data['uploaded_by']),
            'fileName' => $data['file_name'] ?? $data['fileName'],
            'filePath' => $data['file_path'] ?? $data['filePath'],
            'mimeType' => $data['mime_type'] ?? $data['mimeType'] ?? $data['file_type'],
            'fileSize' => (int) ($data['file_size'] ?? $data['fileSize']),
            'createdAt' => isset($data['created_at']) ? Carbon::parse($data['created_at']) : null,
            'updatedAt' => isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : null,
        ]);
    }

    /**
     * Convert DTO to array for persistence or API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'user_id' => $this->userId,
            'file_name' => $this->fileName,
            'file_path' => $this->filePath,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
