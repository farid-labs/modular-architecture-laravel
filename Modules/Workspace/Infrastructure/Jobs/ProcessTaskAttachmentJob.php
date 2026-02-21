<?php

namespace Modules\Workspace\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;

/**
 * Queued job for processing task attachments after upload.
 *
 * This job handles post-upload processing of task attachments including:
 * - Moving files from temporary storage to final location
 * - Organizing files by attachment ID for better management
 * - Logging processing results for audit trail
 *
 * The job is queued to avoid blocking the upload response and to enable
 * retry logic in case of storage failures.
 *
 * @see TaskAttachmentController For upload endpoint
 * @see WorkspaceService For attachment upload orchestration
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
class ProcessTaskAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times to attempt the job before marking as failed.
     */
    public int $tries = 3;

    /**
     * Maximum time in seconds the job may run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param  TaskAttachmentEntity  $attachment  The attachment entity being processed
     * @param  string  $tempDiskPath  The temporary file path where the file was initially stored
     */
    public function __construct(
        private readonly TaskAttachmentEntity $attachment,
        private readonly string $tempDiskPath
    ) {}

    /**
     * Execute the job.
     *
     * Moves the uploaded file from temporary storage to its final organized location.
     * Files are organized in directories by attachment ID for better management
     * and to prevent filename collisions.
     *
     * Final path structure: task-attachments/{attachment_id}/{filename}
     *
     *
     * @throws FileNotFoundException If temporary file not found
     * @throws FilesystemException If file move operation fails
     */
    public function handle(): void
    {
        // Construct final file path organized by attachment ID
        // This prevents filename collisions and enables easy cleanup
        $finalPath = 'task-attachments/'.$this->attachment->getId().'/'.basename($this->tempDiskPath);

        // Move file from temporary location to final organized location
        // Uses public disk for web-accessible file storage
        Storage::disk('public')->move($this->tempDiskPath, $finalPath);

        // TODO: Update attachment entity with final path if needed
        // $this->attachment->updatePath($finalPath);

        // Log successful processing for audit trail and monitoring
        Log::channel('domain')->info('Attachment processed successfully', [
            'attachment_id' => $this->attachment->getId(),
            'final_path' => $finalPath,
        ]);
    }
}
