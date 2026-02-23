<?php

namespace Modules\Workspace\Domain\Entities;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\ValueObjects\FileName;
use Modules\Workspace\Domain\ValueObjects\FilePath;

/**
 * Domain entity representing a task attachment.
 *
 * Encapsulates all metadata related to a file attached to a task,
 * including file information, uploader details, and timestamps.
 *
 * This entity follows Domain-Driven Design (DDD) principles:
 * - Immutability: All properties are readonly after instantiation
 * - Encapsulation: Value objects protect data integrity
 * - Rich domain model: Business logic resides within the entity
 *
 * @see FileName Value object for file name validation
 * @see FilePath Value object for file path validation
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
final readonly class TaskAttachmentEntity
{
    /**
     * Create a new TaskAttachmentEntity instance.
     *
     * @param  int  $id  Unique attachment identifier (auto-incremented)
     * @param  int  $taskId  Related task ID (foreign key reference)
     * @param  int  $uploadedBy  User ID who uploaded the attachment (ownership)
     * @param  string  $mimeType  File MIME type for validation and display
     * @param  int  $fileSize  File size in bytes for quota and validation
     * @param  CarbonInterface|null  $createdAt  Attachment creation timestamp
     * @param  CarbonInterface|null  $updatedAt  Attachment last modification timestamp
     * @param  FileName  $fileName  Value object for validated original file name
     * @param  FilePath  $filePath  Value object for validated storage path
     */
    public function __construct(
        private int $id,
        private int $taskId,
        private int $uploadedBy,
        private string $mimeType,
        private int $fileSize,
        private ?CarbonInterface $createdAt,
        private ?CarbonInterface $updatedAt,
        private FileName $fileName,
        private FilePath $filePath,
    ) {}

    /**
     * Get the unique attachment identifier.
     *
     * @return int The attachment ID
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the related task identifier.
     *
     * @return int The task ID this attachment belongs to
     */
    public function getTaskId(): int
    {
        return $this->taskId;
    }

    /**
     * Get the uploader user identifier.
     *
     * Used for authorization checks (only uploader can delete).
     *
     * @return int The user ID who uploaded this attachment
     */
    public function getUploadedBy(): int
    {
        return $this->uploadedBy;
    }

    /**
     * Get the file path value object.
     *
     * @return FilePath The validated storage path
     */
    public function getFilePathVO(): FilePath
    {
        return $this->filePath;
    }

    /**
     * Get the file name value object.
     *
     * @return FileName The validated original file name
     */
    public function getFileNameVO(): FileName
    {
        return $this->fileName;
    }

    /**
     * Get the file MIME type.
     *
     * @return string The MIME type (e.g., 'image/jpeg', 'application/pdf')
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get the file size in bytes.
     *
     * @return int The file size in bytes
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * Get the creation timestamp.
     *
     * @return CarbonInterface|null The creation date/time or null if not set
     */
    public function getCreatedAt(): ?CarbonInterface
    {
        return $this->createdAt;
    }

    /**
     * Get the last update timestamp.
     *
     * @return CarbonInterface|null The last modification date/time or null if not set
     */
    public function getUpdatedAt(): ?CarbonInterface
    {
        return $this->updatedAt;
    }

    /**
     * Convert entity to array for persistence or serialization.
     *
     * Extracts primitive values from value objects for database storage
     * or JSON serialization. Use this when persisting to repository layer.
     *
     * @return array{
     *     id: int,
     *     task_id: int,
     *     uploaded_by: int,
     *     file_name: string,
     *     file_path: string,
     *     mime_type: string,
     *     file_size: int,
     *     created_at: string|null,
     *     updated_at: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->taskId,
            'uploaded_by' => $this->uploadedBy,
            'file_name' => $this->fileName->value(),
            'file_path' => $this->filePath->value(),
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }

    /**
     * Create entity from array (factory method).
     *
     * Used by repository layer to reconstruct entity from database records.
     * Instantiates value objects from primitive string values.
     *
     * @param  array<string, mixed>  $data  The raw data array from persistence layer
     * @return self The constructed entity instance
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            taskId: (int) $data['task_id'],
            uploadedBy: (int) $data['uploaded_by'],
            mimeType: $data['mime_type'] ?? $data['file_type'] ?? '',
            fileSize: (int) $data['file_size'],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            fileName: new FileName($data['file_name']),
            filePath: new FilePath($data['file_path']),
        );
    }
}
