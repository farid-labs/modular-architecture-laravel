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
 */
final class TaskCommentUpdated implements ShouldBroadcast
{
    use InteractsWithSockets;

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
