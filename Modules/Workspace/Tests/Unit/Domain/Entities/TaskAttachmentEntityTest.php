<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Carbon\Carbon;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\ValueObjects\FileName;
use Modules\Workspace\Domain\ValueObjects\FilePath;
use Modules\Workspace\Domain\ValueObjects\FileSize;
use Modules\Workspace\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for TaskAttachmentEntity.
 *
 * @covers \Modules\Workspace\Domain\Entities\TaskAttachmentEntity
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
#[CoversClass(TaskAttachmentEntity::class)]
class TaskAttachmentEntityTest extends TestCase
{
    /**
     * Test that task attachment entity can be created with valid data.
     */
    #[Test]
    public function test_task_attachment_entity_can_be_created(): void
    {
        $now = Carbon::now();

        $attachment = new TaskAttachmentEntity(
            id: 1,
            taskId: 10,
            uploadedBy: 5,
            mimeType: 'application/pdf',
            fileSize: new FileSize(102400),  // Wrap int in FileSize VO
            createdAt: $now,
            updatedAt: $now,
            fileName: new FileName('document.pdf'),
            filePath: new FilePath('task-attachments/document.pdf')
        );

        $this->assertEquals(1, $attachment->getId());
        $this->assertEquals(10, $attachment->getTaskId());
        $this->assertEquals(5, $attachment->getUploadedBy());
        $this->assertEquals('document.pdf', $attachment->getFileNameVO()->value());
        $this->assertEquals('application/pdf', $attachment->getMimeType());
        // Use getFileSizeVO()->bytes() instead of getFileSize()
        $this->assertEquals(102400, $attachment->getFileSizeVO()->bytes());
    }

    /**
     * Test toArray conversion returns correct structure.
     */
    #[Test]
    public function test_to_array_conversion(): void
    {
        $now = Carbon::now();

        $attachment = new TaskAttachmentEntity(
            id: 1,
            taskId: 10,
            uploadedBy: 5,
            mimeType: 'image/jpeg',
            fileSize: new FileSize(204800),  //  Wrap int in FileSize VO
            createdAt: $now,
            updatedAt: $now,
            fileName: new FileName('image.jpg'),
            filePath: new FilePath('task-attachments/image.jpg')
        );

        $array = $attachment->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals(10, $array['task_id']);
        $this->assertEquals('image.jpg', $array['file_name']);
        $this->assertEquals('image/jpeg', $array['mime_type']);
        // file_size is extracted from FileSize VO in toArray()
        $this->assertEquals(204800, $array['file_size']);
    }
}
