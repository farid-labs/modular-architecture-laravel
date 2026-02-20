<?php

namespace Modules\Workspace\Domain\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Presentation\Resources\TaskCommentResource;

/**
 * Event fired when an attachment is successfully uploaded.
 */
final readonly class TaskAttachmentUploaded
{
    public function __construct(
        public TaskEntity $task,
        public TaskAttachmentEntity $attachment,
        public int $actorId
    ) {}
    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
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
            'attachment' => (new TaskCommentResource($this->attachment))->toArray(request()),
            'actor_id' => $this->actorId,
        ];
    }
}
