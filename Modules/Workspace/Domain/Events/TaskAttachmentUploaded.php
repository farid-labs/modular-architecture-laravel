<?php

namespace Modules\Workspace\Domain\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Presentation\Resources\TaskAttachmentResource;

/**
 * Event fired when attachments are successfully uploaded to a task.
 *
 * Broadcasts to private channel for real-time notifications.
 * Used to notify workspace members about new task attachments.
 * Triggers after file validation and storage completion.
 *
 * Supports batch uploads (multiple attachments per event).
 *
 * @see ProcessTaskAttachmentJob For async file processing
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
final readonly class TaskAttachmentUploaded
{
    /**
     * Create a new event instance.
     *
     * @param  TaskEntity  $task  The task entity the attachments belong to
     * @param  array<int, TaskAttachmentEntity>  $attachments  The uploaded attachment entities
     * @param  int  $actorId  The user ID who uploaded the attachments
     */
    public function __construct(
        public TaskEntity $task,
        public array $attachments,
        public int $actorId
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * Broadcasts to task-specific private channel for real-time updates.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("task.{$this->task->getId()}")];
    }

    /**
     * Get the data to broadcast.
     *
     * Includes attachment metadata and actor information for client-side rendering.
     * Transforms all attachments to resources for frontend display.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            // Attachment resources data for frontend display (supports multiple attachments)
            'attachments' => TaskAttachmentResource::collection($this->attachments)->toArray(request()),
            // User ID who performed the action
            'actor_id' => $this->actorId,
            // Number of attachments uploaded (for client-side optimization)
            'count' => count($this->attachments),
            // Task ID for client-side routing
            'task_id' => $this->task->getId(),
        ];
    }
}
