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
        private readonly int $id,
        private readonly int $taskId,
        private readonly int $userId,
        private readonly string $comment,
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

    public function getComment(): string
    {
        return $this->comment;
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
            'id' => $this->id,
            'task_id' => $this->taskId,
            'user_id' => $this->userId,
            'comment' => $this->comment,
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
