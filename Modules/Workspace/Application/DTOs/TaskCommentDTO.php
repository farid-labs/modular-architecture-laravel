<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * Data Transfer Object for Task Comment.
 * Handles input/output mapping for comment operations.
 */
class TaskCommentDTO extends DataTransferObject
{
    public int $taskId;

    public int $userId;

    public string $comment;

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
            'userId' => (int) ($data['user_id'] ?? $data['userId'] ?? $data['uploaded_by'] ?? $data['author_id']),
            'comment' => $data['comment'],
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
            'comment' => $this->comment,
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
