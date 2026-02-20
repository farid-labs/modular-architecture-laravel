<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Carbon\Carbon;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\ValueObjects\FileName;
use Modules\Workspace\Domain\ValueObjects\FilePath;
use Modules\Workspace\Tests\TestCase;

class TaskAttachmentEntityTest extends TestCase
{
    /**
     * Test that a TaskAttachmentEntity can be properly created
     * and that getters return the expected values.
     */
    public function test_task_attachment_entity_can_be_created(): void
    {
        $now = Carbon::now();

        $attachment = new TaskAttachmentEntity(
            1,                         // ID
            10,                        // Task ID
            5,                         // User ID (uploaded by)
            'application/pdf', // File path
            102400,            // File name
            $now,                      // created_at
            $now,                  // updated_at
            new FileName('document.pdf'),
            new FilePath('attachments/document.pdf')

        );

        $this->assertEquals(1, $attachment->getId());
        $this->assertEquals(10, $attachment->getTaskId());
        $this->assertEquals(5, $attachment->getUserId());
        $this->assertEquals('document.pdf', $attachment->getFileNameVO());
        $this->assertEquals('application/pdf', $attachment->getMimeType());
        $this->assertEquals(102400, $attachment->getFileSize());
    }

    /**
     * Test that the entity can convert itself to an array correctly
     * (useful for API resources or serialization).
     */
    public function test_to_array_conversion(): void
    {
        $now = Carbon::now();
        $attachment = new TaskAttachmentEntity(
            1,
            10,
            5,
            'attachments/image.jpg',
            204800,
            $now,
            $now,
            new FileName('image.jpg'),
            new FilePath('attachments/image.jpg')

        );

        $array = $attachment->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals(10, $array['task_id']);
        $this->assertEquals('image.jpg', $array['file_name']);
        $this->assertEquals('image/jpeg', $array['mime_type']);
    }
}
