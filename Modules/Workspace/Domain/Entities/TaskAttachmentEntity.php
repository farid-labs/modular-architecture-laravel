<?php

namespace Modules\Workspace\Domain\Entities;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\ValueObjects\FileName;
use Modules\Workspace\Domain\ValueObjects\FilePath;

/**
 * Domain entity representing a task attachment.
 * Immutable value object with full encapsulation.
 */
class TaskAttachmentEntity
{
    public function __construct(

        // Unique attachment identifier
        private readonly int $id,

        // Related task ID
        private readonly int $taskId,

        // User who uploaded the attachment
        private readonly int $userId,

        // MIME type of the file
        private readonly string $mimeType,

        // File size in bytes
        private readonly int $fileSize,

        // Attachment creation timestamp
        private readonly ?CarbonInterface $createdAt,

        // Attachment last update timestamp
        private readonly ?CarbonInterface $updatedAt,

        private readonly FileName $fileName,

        private readonly FilePath $filePath,
    ) {}

    // Get attachment ID
    public function getId(): int
    {
        return $this->id;
    }

    // Get related task ID
    public function getTaskId(): int
    {
        return $this->taskId;
    }

    // Get uploader user ID
    public function getUserId(): int
    {
        return $this->userId;
    }

    // Get stored file path
    public function getFilePathVO(): FilePath
    {
        return $this->filePath;
    }

    // Get original file name
    public function getFileNameVO(): FileName
    {
        return $this->fileName;
    }

    // Get file MIME type
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    // Get file size in bytes
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    // Get creation timestamp
    public function getCreatedAt(): ?CarbonInterface
    {
        return $this->createdAt;
    }

    // Get last update timestamp
    public function getUpdatedAt(): ?CarbonInterface
    {
        return $this->updatedAt;
    }

    /**
     * Convert entity to array for persistence or serialization.
     *
     * @return array{
     *     id: int,
     *     task_id: int,
     *     user_id: int,
     *     file_path: string,
     *     file_name: string,
     *     mime_type: string,
     *     file_size: int,
     *     created_at: string|null,
     *     updated_at: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            // Attachment ID
            'id' => $this->id,

            // Related task ID
            'task_id' => $this->taskId,

            // Uploader user ID
            'user_id' => $this->userId,

            // Stored file path
            'file_path' => $this->filePath,

            // Original file name
            'file_name' => $this->fileName,

            // MIME type
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
