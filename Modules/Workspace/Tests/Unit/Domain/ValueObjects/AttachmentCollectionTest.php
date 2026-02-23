<?php

namespace Modules\Workspace\Tests\Unit\Domain\ValueObjects;

use Illuminate\Http\UploadedFile;
use Modules\Workspace\Domain\Exceptions\AuthorizationException;
use Modules\Workspace\Domain\ValueObjects\AttachmentCollection;
use Modules\Workspace\Domain\ValueObjects\AttachmentUpload;
use Modules\Workspace\Domain\ValueObjects\FileSize;
use Modules\Workspace\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for AttachmentCollection value object.
 *
 * Validates domain rules for attachment collections:
 * - Minimum/maximum file count constraints (1-3 files)
 * - Individual file validation through AttachmentUpload
 * - Total size calculation across collection
 * - Immutable collection behavior
 * - Iterator support for foreach operations
 *
 * @covers \Modules\Workspace\Domain\ValueObjects\AttachmentCollection
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
#[CoversClass(AttachmentCollection::class)]
class AttachmentCollectionTest extends TestCase
{
    #[Test]
    public function test_valid_collection_creation(): void
    {
        $files = [
            UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('file2.png', 200, 'image/png'),
        ];

        $collection = new AttachmentCollection($files);

        // Use iterator_count instead of assertCount to avoid GeneratorNotSupportedException
        $this->assertEquals(2, iterator_count($collection->getIterator()));
        $this->assertEquals(2, $collection->count());
        $this->assertFalse($collection->isEmpty());

        // Verify each item is properly wrapped AttachmentUpload
        foreach ($collection as $upload) {
            $this->assertInstanceOf(AttachmentUpload::class, $upload);
        }
    }

    #[Test]
    public function test_minimum_file_count_enforced(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessageMatches('/'.preg_quote(__('workspaces.attachment_min_count', ['min' => 1]), '/').'/i');

        new AttachmentCollection([]);
    }

    #[Test]
    public function test_maximum_file_count_enforced(): void
    {
        $files = array_map(fn () => UploadedFile::fake()->create('file.pdf', 100, 'application/pdf'), range(1, 4));

        $this->expectException(AuthorizationException::class);
        // Match actual exception message format used in AttachmentCollection
        $this->expectExceptionMessageMatches('/Maximum 3 files allowed per request/i');

        new AttachmentCollection($files);
    }

    #[Test]
    public function test_individual_file_validation(): void
    {
        $invalidFile = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');
        $validFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessageMatches('/'.preg_quote(__('workspaces.invalid_file_type'), '/').'/i');

        new AttachmentCollection([$validFile, $invalidFile]);
    }

    #[Test]
    public function test_total_size_calculation(): void
    {
        $files = [
            UploadedFile::fake()->create('small.pdf', 100, 'application/pdf'),  // 100KB
            UploadedFile::fake()->create('medium.pdf', 500, 'application/pdf'), // 500KB
            UploadedFile::fake()->create('large.pdf', 1000, 'application/pdf'), // 1000KB
        ];

        $collection = new AttachmentCollection($files);
        $totalSize = $collection->totalSize();

        $this->assertInstanceOf(FileSize::class, $totalSize);
        $this->assertEquals(1600 * 1024, $totalSize->bytes()); // Convert KB to bytes
    }

    #[Test]
    public function test_collection_immutability(): void
    {
        $files = [UploadedFile::fake()->create('file.pdf', 100, 'application/pdf')];
        $collection = new AttachmentCollection($files);

        // Verify we can iterate without modifying collection
        $count = 0;
        foreach ($collection as $upload) {
            $count++;
        }
        $this->assertEquals(1, $count);
        $this->assertEquals(1, $collection->count());
    }

    #[Test]
    public function test_foreach_iteration_support(): void
    {
        $files = [
            UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('b.pdf', 200, 'application/pdf'),
        ];

        $collection = new AttachmentCollection($files);
        $filenames = [];

        foreach ($collection as $upload) {
            $filenames[] = $upload->getFileName()->value();
        }

        $this->assertEquals(['a.pdf', 'b.pdf'], $filenames);
    }
}
