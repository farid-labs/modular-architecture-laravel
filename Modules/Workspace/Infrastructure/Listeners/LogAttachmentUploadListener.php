<?php

namespace Modules\Workspace\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Workspace\Domain\Events\TaskAttachmentUploaded;

/**
 * Listener to log attachment upload events.
 * Records attachment activity for audit trail.
 *
 * Supports batch uploads (multiple attachments per event).
 *
 * @see TaskAttachmentUploaded Event with multiple attachments
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
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
        // Log each attachment in the batch
        foreach ($event->attachments as $attachment) {
            Log::channel('domain')->info('Task attachment uploaded', [
                'task_id' => $event->task->getId(),
                'attachment_id' => $attachment->getId(),
                'actor_id' => $event->actorId,
                'file_name' => $attachment->getFileNameVO()->value(),
                'file_size' => $attachment->getFileSizeVO()->bytes(),
                'mime_type' => $attachment->getMimeType(),
            ]);
        }
    }
}
