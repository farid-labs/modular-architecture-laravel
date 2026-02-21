<?php

namespace Modules\Workspace\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\Workspace\Domain\Entities\TaskEntity;

/**
 * Event fired when a task is marked as completed.
 * Broadcasts to private channel for real-time notifications.
 */
final class TaskCompleted implements ShouldBroadcast
{
    use InteractsWithSockets;

    /**
     * Create a new event instance.
     *
     * @param  TaskEntity  $task  The completed task entity
     * @param  int  $actorId  The user ID who completed the task
     */
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
            'task_id' => $this->task->getId(),
            'task_title' => $this->task->getTitleVO()->value(),
            'status' => $this->task->getStatus()->value,
            'actor_id' => $this->actorId,
            'event_type' => 'task_completed',
            'completed_at' => now()->toIso8601String(),
        ];
    }
}
