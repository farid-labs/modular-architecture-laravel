<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * Data Transfer Object for Task Attachment operations.
 *
 * Encapsulates file attachment metadata for task-related operations.
 * Handles input/output mapping for attachment upload and retrieval.
 * Supports both snake_case and camelCase array keys for flexibility.
 */
class TaskAttachmentDTO extends DataTransferObject
{
    /**
     * Related task ID.
     * Associates the attachment with a specific task.
     */
    public int $taskId;

    /**
     * User ID who uploaded the attachment.
     * Tracks the owner/uploader of the file.
     */
    public int $userId;

    /**
     * Original file name.
     * Preserves the client-side file name for display purposes.
     */
    public string $fileName;

    /**
     * Stored file path.
     * Location of the file in the storage system.
     */
    public string $filePath;

    /**
     * File MIME type.
     * Used for content-type validation and display.
     */
    public string $mimeType;

    /**
     * File size in bytes.
     * Used for storage quota and validation checks.
     */
    public int $fileSize;

    /**
     * Attachment creation timestamp.
     * Automatically set when attachment is uploaded.
     */
    public ?CarbonInterface $createdAt = null;

    /**
     * Attachment last update timestamp.
     * Updated whenever attachment metadata is modified.
     */
    public ?CarbonInterface $updatedAt = null;

    /**
     * Create DTO from array (supports both snake_case and camelCase).
     *
     * Maps various key formats (snake_case, camelCase) to consistent property names.
     * Accepts multiple field name variations for user ID and MIME type.
     * Parses timestamps to Carbon instances if provided.
     *
     * @param  array<string, mixed>  $data  Associative array containing attachment data
     */
    public static function fromArray(array $data): self
    {
        return new self([
            // Map task ID from snake_case or camelCase
            'taskId' => (int) ($data['task_id'] ?? $data['taskId']),

            // Map user ID from multiple possible field names
            'userId' => (int) ($data['user_id'] ?? $data['userId'] ?? $data['uploaded_by']),

            // Map file name from snake_case or camelCase
            'fileName' => $data['file_name'] ?? $data['fileName'],

            // Map file path from snake_case or camelCase
            'filePath' => $data['file_path'] ?? $data['filePath'],

            // Map MIME type from multiple possible field names
            'mimeType' => $data['mime_type'] ?? $data['mimeType'] ?? $data['file_type'],

            // Map file size from snake_case or camelCase
            'fileSize' => (int) ($data['file_size'] ?? $data['fileSize']),

            // Parse creation timestamp if provided
            'createdAt' => isset($data['created_at']) ? Carbon::parse($data['created_at']) : null,

            // Parse update timestamp if provided
            'updatedAt' => isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : null,
        ]);
    }

    /**
     * Convert DTO to array for persistence or API responses.
     *
     * Transforms camelCase property names to snake_case for database persistence.
     * Formats timestamps as ISO 8601 strings for API consistency.
     *
     * @return array<string, mixed> Associative array with snake_case keys
     */
    public function toArray(): array
    {
        return [
            // Related task ID
            'task_id' => $this->taskId,

            // Uploader user ID
            'user_id' => $this->userId,

            // Original file name
            'file_name' => $this->fileName,

            // Stored file path
            'file_path' => $this->filePath,

            // File MIME type
            'mime_type' => $this->mimeType,

            // File size in bytes
            'file_size' => $this->fileSize,

            // ISO 8601 formatted creation timestamp
            'created_at' => $this->createdAt?->toIso8601String(),

            // ISO 8601 formatted update timestamp
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
