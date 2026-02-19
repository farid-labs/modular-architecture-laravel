<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Carbon\Carbon;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
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
            'attachments/document.pdf', // File path
            'document.pdf',            // File name
            'application/pdf',         // MIME type
            102400,                    // File size
            $now,                      // created_at
            $now                       // updated_at
        );

        $this->assertEquals(1, $attachment->getId());
        $this->assertEquals(10, $attachment->getTaskId());
        $this->assertEquals(5, $attachment->getUserId());
        $this->assertEquals('document.pdf', $attachment->getFileName());
        $this->assertEquals('application/pdf', $attachment->getMimeType());
        $this->assertEquals(102400, $attachment->getFileSize());
    }

    /**
     * Test that the entity can convert itself to an array correctly
     * (useful for API resources or serialization).
     */
    public function test_to_array_conversion(): void
    {
        $attachment = new TaskAttachmentEntity(
            1,
            10,
            5,
            'attachments/image.jpg',
            'image.jpg',
            'image/jpeg',
            204800
        );

        $array = $attachment->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals(10, $array['task_id']);
        $this->assertEquals('image.jpg', $array['file_name']);
        $this->assertEquals('image/jpeg', $array['mime_type']);
    }
}
