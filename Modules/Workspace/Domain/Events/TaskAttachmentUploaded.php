<?php

namespace Modules\Workspace\Domain\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Presentation\Resources\TaskAttachmentResource;

/**
 * Event fired when an attachment is successfully uploaded to a task.
 *
 * Broadcasts to private channel for real-time notifications.
 * Used to notify workspace members about new task attachments.
 * Triggers after file validation and storage completion.
 *
 * @see ProcessTaskAttachmentJob For async file processing
 */
final readonly class TaskAttachmentUploaded
{
    /**
     * Create a new event instance.
     *
     * @param  TaskEntity  $task  The task entity the attachment belongs to
     * @param  TaskAttachmentEntity  $attachment  The uploaded attachment entity
     * @param  int  $actorId  The user ID who uploaded the attachment
     */
    public function __construct(
        public TaskEntity $task,
        public TaskAttachmentEntity $attachment,
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
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            // Attachment resource data for frontend display
            'attachment' => (new TaskAttachmentResource($this->attachment))->toArray(request()),
            // User ID who performed the action
            'actor_id' => $this->actorId,
        ];
    }
}
