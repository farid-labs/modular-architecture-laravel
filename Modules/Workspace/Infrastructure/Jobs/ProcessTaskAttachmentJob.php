<?php

namespace Modules\Workspace\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;

class ProcessTaskAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        private readonly TaskAttachmentEntity $attachment,
        private readonly string $tempDiskPath
    ) {}

    public function handle(): void
    {
        $finalPath = 'task-attachments/'.$this->attachment->getId().'/'.basename($this->tempDiskPath);

        Storage::disk('public')->move($this->tempDiskPath, $finalPath);

        // $this->attachment->updatePath($finalPath);

        Log::channel('domain')->info('Attachment processed successfully', [
            'attachment_id' => $this->attachment->getId(),
            'final_path' => $finalPath,
        ]);
    }
}
