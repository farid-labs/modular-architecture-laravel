<?php

namespace Modules\Workspace\Domain\Entities;

use Carbon\CarbonInterface;

/**
 * Domain entity representing a task attachment.
 * Immutable value object with full encapsulation.
 */
class TaskAttachmentEntity
{
    public function __construct(
        private readonly int $id,
        private readonly int $taskId,
        private readonly int $userId,
        private readonly string $filePath,
        private readonly string $fileName,
        private readonly string $mimeType,
        private readonly int $fileSize,
        private readonly ?CarbonInterface $createdAt = null,
        private readonly ?CarbonInterface $updatedAt = null
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getCreatedAt(): ?CarbonInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?CarbonInterface
    {
        return $this->updatedAt;
    }

    /**
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
            'id' => $this->id,
            'task_id' => $this->taskId,
            'user_id' => $this->userId,
            'file_path' => $this->filePath,
            'file_name' => $this->fileName,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
