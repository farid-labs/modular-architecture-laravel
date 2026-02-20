<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Carbon\Carbon;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\ValueObjects\FileName;
use Modules\Workspace\Domain\ValueObjects\FilePath;
use Modules\Workspace\Tests\TestCase;

class TaskAttachmentEntityTest extends TestCase
{
    public function test_task_attachment_entity_can_be_created(): void
    {
        $now = Carbon::now();

        $attachment = new TaskAttachmentEntity(
            1,                                 // id
            10,                                // taskId
            5,                                 // userId
            'application/pdf',                 // mimeType (string)
            102400,                            // fileSize (int)
            $now,                              // createdAt
            $now,                              // updatedAt
            new FileName('document.pdf'),      // fileName
            new FilePath('task-attachments/document.pdf')  // filePath
        );

        $this->assertEquals(1, $attachment->getId());
        $this->assertEquals(10, $attachment->getTaskId());
        $this->assertEquals(5, $attachment->getUserId());
        $this->assertEquals('document.pdf', $attachment->getFileNameVO()->value());
        $this->assertEquals('application/pdf', $attachment->getMimeType());
        $this->assertEquals(102400, $attachment->getFileSize());
    }

    public function test_to_array_conversion(): void
    {
        $now = Carbon::now();

        $attachment = new TaskAttachmentEntity(
            1,                                 // id
            10,                                // taskId
            5,                                 // userId
            'image/jpeg',                      // mimeType
            204800,                            // fileSize
            $now,
            $now,
            new FileName('image.jpg'),
            new FilePath('task-attachments/image.jpg')
        );

        $array = $attachment->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals(10, $array['task_id']);
        $this->assertEquals('image.jpg', $array['file_name']);
        $this->assertEquals('image/jpeg', $array['mime_type']);
        $this->assertEquals(204800, $array['file_size']);
    }
}
