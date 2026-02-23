<?php

namespace Modules\Workspace\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Workspace\Domain\Events\TaskAttachmentUploaded;
use Modules\Workspace\Domain\Events\TaskCommentAdded;

/**
 * Listener to log task activity events.
 * Records task comments and attachments for audit trail.
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
class LogTaskActivityListener
{
    /**
     * Handle the event.
     *
     * @param  TaskCommentAdded|TaskAttachmentUploaded  $event  The activity event
     */
    public function handle(TaskCommentAdded|TaskAttachmentUploaded $event): void
    {
        if ($event instanceof TaskCommentAdded) {
            // Handle comment event
            Log::channel('domain')->info('Task comment created', [
                'task_id' => $event->task->getId(),
                'comment_id' => $event->comment->getId(),
                'actor_id' => $event->actorId,
            ]);
        } elseif ($event instanceof TaskAttachmentUploaded) {
            // Handle attachment event (supports multiple attachments)
            foreach ($event->attachments as $attachment) {
                Log::channel('domain')->info('Task attachment created', [
                    'task_id' => $event->task->getId(),
                    'attachment_id' => $attachment->getId(),
                    'actor_id' => $event->actorId,
                    'file_name' => $attachment->getFileNameVO()->value(),
                ]);
            }
        }
    }
}
