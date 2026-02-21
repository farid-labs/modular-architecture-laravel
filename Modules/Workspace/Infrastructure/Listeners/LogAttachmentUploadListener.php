<?php

namespace Modules\Workspace\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Workspace\Domain\Events\TaskAttachmentUploaded;

/**
 * Listener to log attachment upload events.
 * Records attachment activity for audit trail.
 */
class LogAttachmentUploadListener
{
    /**
     * Handle the event.
     *
     * @param  TaskAttachmentUploaded  $event  The attachment upload event
     */
    public function handle(TaskAttachmentUploaded $event): void
    {
        Log::channel('domain')->info('Task attachment uploaded', [
            'task_id' => $event->task->getId(),
            'attachment_id' => $event->attachment->getId(),
            'actor_id' => $event->actorId,
            'file_name' => $event->attachment->getFileNameVO()->value(),
            'file_size' => $event->attachment->getFileSize(),
            'mime_type' => $event->attachment->getMimeType(),
        ]);
    }
}
