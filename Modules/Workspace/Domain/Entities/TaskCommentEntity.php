<?php

namespace Modules\Workspace\Domain\Entities;

/**
 * Domain entity representing a task comment
 * Immutable value object with full encapsulation
 */
class TaskCommentEntity
{
    public function __construct(
        private readonly int $id,
        private readonly int $taskId,
        private readonly int $userId,
        private readonly string $comment
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

    public function updateComment(string $newComment): self
    {
        return new self(
            $this->id,
            $this->taskId,
            $this->userId,
            $newComment
        );
    }

    /**
     * @return array{
     *     id: int,
     *     task_id: int,
     *     user_id: int,
     *     comment: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->taskId,
            'user_id' => $this->userId,
            'comment' => $this->comment,
        ];
    }
}
