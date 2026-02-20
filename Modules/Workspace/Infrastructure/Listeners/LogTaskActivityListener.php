<?php

namespace Modules\Workspace\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Workspace\Domain\Events\TaskAttachmentUploaded;
use Modules\Workspace\Domain\Events\TaskCommentAdded;

class LogTaskActivityListener
{
    public function handle(TaskCommentAdded|TaskAttachmentUploaded $event): void
    {
        $type = $event instanceof TaskCommentAdded ? 'comment' : 'attachment';

        Log::channel('domain')->info("Task {$type} created", [
            'task_id' => $event->task->getId(),
            'actor_id' => $event->actorId,
            'details' => $type === 'comment'
                ? ['comment_id' => $event->comment->getId()]
                : ['attachment_id' => $event->attachment->getId()],
        ]);
    }
}
