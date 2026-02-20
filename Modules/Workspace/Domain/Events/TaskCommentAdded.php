<?php

namespace Modules\Workspace\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Presentation\Resources\TaskCommentResource;

final class TaskCommentAdded implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct(
        public TaskEntity $task,
        public TaskCommentEntity $comment,
        public int $actorId
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("task.{$this->task->getId()}")];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'comment' => (new TaskCommentResource($this->comment))->toArray(request()),
            'actor_id' => $this->actorId,
        ];
    }
}
