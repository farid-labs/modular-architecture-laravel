<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * Data Transfer Object for Task Comment operations.
 *
 * Encapsulates comment data for task-related operations.
 * Handles input/output mapping for comment creation and updates.
 * Supports both snake_case and camelCase array keys for flexibility.
 */
class TaskCommentDTO extends DataTransferObject
{
    /**
     * Related task ID.
     * Associates the comment with a specific task.
     */
    public int $taskId;

    /**
     * User ID who wrote the comment.
     * Tracks the author of the comment.
     */
    public int $userId;

    /**
     * Comment content.
     * The actual text content of the comment.
     * Must be between 3-2000 characters.
     */
    public string $comment;

    /**
     * Comment creation timestamp.
     * Automatically set when comment is created.
     */
    public ?CarbonInterface $createdAt = null;

    /**
     * Comment last update timestamp.
     * Updated whenever comment content is modified.
     */
    public ?CarbonInterface $updatedAt = null;

    /**
     * Create DTO from array (supports both snake_case and camelCase).
     *
     * Maps various key formats (snake_case, camelCase) to consistent property names.
     * Accepts multiple field name variations for user ID.
     * Parses timestamps to Carbon instances if provided.
     *
     * @param  array<string, mixed>  $data  Associative array containing comment data
     */
    public static function fromArray(array $data): self
    {
        return new self([
            // Map task ID from snake_case or camelCase
            'taskId' => (int) ($data['task_id'] ?? $data['taskId']),

            // Map user ID from multiple possible field names
            'userId' => (int) ($data['user_id'] ?? $data['userId'] ?? $data['uploaded_by'] ?? $data['author_id']),

            // Comment content (required)
            'comment' => $data['comment'],

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
