<?php

namespace Modules\Workspace\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Presentation\Resources\TaskCommentResource;

/**
 * Event fired when a new comment is added to a task.
 *
 * Broadcasts to private channel for real-time notifications.
 * Used to notify workspace members about new task comments.
 *
 * @see TaskCommentUpdated For comment update events
 */
final class TaskCommentAdded implements ShouldBroadcast
{
    use InteractsWithSockets;

    /**
     * Create a new event instance.
     *
     * @param  TaskEntity  $task  The task entity the comment belongs to
     * @param  TaskCommentEntity  $comment  The newly created comment entity
     * @param  int  $actorId  The user ID who added the comment
     */
    public function __construct(
        public TaskEntity $task,
        public TaskCommentEntity $comment,
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
     * Includes comment details and actor information for client-side rendering.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            // Comment resource data for frontend display
            'comment' => (new TaskCommentResource($this->comment))->toArray(request()),
            // User ID who performed the action
            'actor_id' => $this->actorId,
        ];
    }
}
