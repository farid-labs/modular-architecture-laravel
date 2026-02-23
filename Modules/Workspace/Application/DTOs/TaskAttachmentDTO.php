<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Modules\Workspace\Domain\ValueObjects\FileName;
use Modules\Workspace\Domain\ValueObjects\FilePath;
use Modules\Workspace\Domain\ValueObjects\FileSize;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * Data Transfer Object for Task Attachment operations.
 *
 * Encapsulates file attachment metadata for task-related operations.
 * Handles input/output mapping for attachment upload and retrieval.
 * Integrates with domain value objects for type safety and validation.
 * Supports both snake_case and camelCase array keys for flexibility.
 *
 * @see FileName Value object for file name validation
 * @see FilePath Value object for file path validation
 * @see FileSize Value object for file size validation
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
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
     * Used for authorization checks (only uploader can delete).
     */
    public int $uploadedBy;

    /**
     * Original file name value object.
     * Preserves the client-side file name for display purposes.
     * Validated for length and sanitization rules.
     */
    public FileName $fileName;

    /**
     * Stored file path value object.
     * Location of the file in the storage system.
     * Validated for security (prevents directory traversal).
     */
    public FilePath $filePath;

    /**
     * File MIME type.
     * Used for content-type validation and display.
     * Allowed types: image/jpeg, image/png, image/gif, image/webp, application/pdf
     */
    public string $mimeType;

    /**
     * File size value object.
     * Used for storage quota and validation checks.
     * Maximum allowed size: 10MB (10,485,760 bytes)
     */
    public FileSize $fileSize;

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
     * Instantiates value objects from primitive values for domain integrity.
     *
     * @param  array<string, mixed>  $data  Associative array containing attachment data
     * @return self The constructed DTO instance
     */
    public static function fromArray(array $data): self
    {
        return new self([
            // Map task ID from snake_case or camelCase
            'taskId' => (int) ($data['task_id'] ?? $data['taskId']),

            // Map uploader user ID from multiple possible field names
            'uploadedBy' => (int) ($data['user_id'] ?? $data['userId'] ?? $data['uploaded_by']),

            // Map file name from snake_case or camelCase and create Value Object
            'fileName' => new FileName($data['file_name'] ?? $data['fileName']),

            // Map file path from snake_case or camelCase and create Value Object
            'filePath' => new FilePath($data['file_path'] ?? $data['filePath']),

            // Map MIME type from multiple possible field names
            'mimeType' => $data['mime_type'] ?? $data['mimeType'] ?? $data['file_type'],

            // Map file size from snake_case or camelCase and create Value Object
            'fileSize' => new FileSize((int) ($data['file_size'] ?? $data['fileSize'])),

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
     * Extracts primitive values from value objects for serialization.
     *
     * @return array<string, mixed> Associative array with snake_case keys
     */
    public function toArray(): array
    {
        return [
            // Related task ID
            'task_id' => $this->taskId,

            // Uploader user ID
            'uploaded_by' => $this->uploadedBy,

            // Original file name (extracted from Value Object)
            'file_name' => $this->fileName->value(),

            // Stored file path (extracted from Value Object)
            'file_path' => $this->filePath->value(),

            // File MIME type
            'mime_type' => $this->mimeType,

            // File size in bytes (extracted from Value Object)
            'file_size' => $this->fileSize->bytes(),

            // ISO 8601 formatted creation timestamp
            'created_at' => $this->createdAt?->toIso8601String(),

            // ISO 8601 formatted update timestamp
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
