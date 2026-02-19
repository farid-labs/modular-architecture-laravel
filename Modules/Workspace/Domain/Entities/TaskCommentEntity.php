<?php

namespace Modules\Workspace\Domain\Entities;

use Carbon\CarbonInterface;

/**
 * Domain entity representing a task comment.
 * Immutable value object with full encapsulation.
 */
class TaskCommentEntity
{
    public function __construct(
        // Unique comment identifier
        private readonly int $id,

        // Related task ID
        private readonly int $taskId,

        // User who wrote the comment
        private readonly int $userId,

        // Comment content
        private readonly string $comment,

        // Comment creation timestamp
        private readonly ?CarbonInterface $createdAt = null,

        // Comment last update timestamp
        private readonly ?CarbonInterface $updatedAt = null
    ) {}

    // Get comment ID
    public function getId(): int
    {
        return $this->id;
    }

    // Get related task ID
    public function getTaskId(): int
    {
        return $this->taskId;
    }

    // Get author user ID
    public function getUserId(): int
    {
        return $this->userId;
    }

    // Get comment content
    public function getComment(): string
    {
        return $this->comment;
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
     * Create a new instance with an updated comment (Immutable pattern).
     */
    public function updateComment(string $newComment): self
    {
        return new self(
            $this->id,
            $this->taskId,
            $this->userId,
            $newComment,
            $this->createdAt,
            $this->updatedAt
        );
    }

    /**
     * Convert entity to array for persistence or serialization.
     *
     * @return array{
     *     id: int,
     *     task_id: int,
     *     user_id: int,
     *     comment: string,
     *     created_at: string|null,
     *     updated_at: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            // Comment ID
            'id' => $this->id,

            // Related task ID
            'task_id' => $this->taskId,

            // Author user ID
            'user_id' => $this->userId,

            // Comment content
            'comment' => $this->comment,

            // ISO 8601 formatted creation timestamp
            'created_at' => $this->createdAt?->toIso8601String(),

            // ISO 8601 formatted update timestamp
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
