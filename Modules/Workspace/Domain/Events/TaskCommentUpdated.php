<?php

namespace Modules\Workspace\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Presentation\Resources\TaskCommentResource;

/**
 * Event fired when a task comment is updated.
 * Broadcasts to private channel for real-time updates.
 */
final class TaskCommentUpdated implements ShouldBroadcast
{
    use InteractsWithSockets;

    /**
     * Create a new event instance.
     *
     * @param  TaskEntity  $task  The task entity
     * @param  TaskCommentEntity  $comment  The updated comment entity
     * @param  int  $actorId  The user ID who updated the comment
     */
    public function __construct(
        public TaskEntity $task,
        public TaskCommentEntity $comment,
        public int $actorId
    ) {}

    /**
     * Get the channels the event should broadcast on.
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
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'comment' => (new TaskCommentResource($this->comment))->toArray(request()),
            'actor_id' => $this->actorId,
            'event_type' => 'comment_updated',
        ];
    }
}
