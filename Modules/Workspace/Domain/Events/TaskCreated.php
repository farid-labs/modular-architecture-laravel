<?php

namespace Modules\Workspace\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Presentation\Resources\TaskResource;

/**
 * Event fired when a new task is created.
 */
final class TaskCreated implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct(
        public TaskEntity $task,
        public int $actorId
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("project.{$this->task->getProjectId()}")];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task' => (new TaskResource($this->task))->toArray(request()),
            'actor_id' => $this->actorId,
            'event_type' => 'task_created',
        ];
    }
}
