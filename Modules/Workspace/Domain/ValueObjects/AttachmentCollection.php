<?php

namespace Modules\Workspace\Domain\ValueObjects;

use Illuminate\Http\UploadedFile;
use IteratorAggregate;
use Modules\Workspace\Domain\Exceptions\AuthorizationException;
use Traversable;

/**
 * Value object representing a collection of attachment uploads.
 *
 * Encapsulates validation rules for multiple file uploads:
 * - Minimum files: 1
 * - Maximum files: 3 per request
 * - Each file must be valid AttachmentUpload
 *
 * Ensures collection integrity across the domain layer.
 * Immutable by design (readonly class).
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 *
 * @implements IteratorAggregate<int, AttachmentUpload>
 */
final readonly class AttachmentCollection implements IteratorAggregate
{
    /**
     * Maximum allowed attachments per request.
     */
    private const MAX_ATTACHMENTS = 3;

    /**
     * Minimum required attachments per request.
     */
    private const MIN_ATTACHMENTS = 1;

    /**
     * The collection of attachment uploads.
     *
     * @var array<int, AttachmentUpload>
     */
    private array $uploads;

    /**
     * Create a new AttachmentCollection value object.
     *
     * Validates the file collection before instantiation.
     *
     * @param  array<int, UploadedFile>  $files  The uploaded files array
     *
     * @throws AuthorizationException If collection validation fails
     */
    public function __construct(array $files)
    {
        $this->ensureIsValid($files);
        $this->uploads = array_map(
            fn (UploadedFile $file): AttachmentUpload => new AttachmentUpload($file),
            $files
        );
    }

    /**
     * Validate the file collection.
     *
     * Checks for minimum/maximum file count and individual file validity.
     *
     * @param  array<int, UploadedFile>  $files  The files to validate
     *
     * @throws AuthorizationException If validation fails
     */
    private function ensureIsValid(array $files): void
    {
        $fileCount = count($files);

        // Check minimum files
        if ($fileCount < self::MIN_ATTACHMENTS) {
            throw new AuthorizationException(
                'attachment_min_count',
                ['min' => self::MIN_ATTACHMENTS]
            );
        }

        // Check maximum files
        if ($fileCount > self::MAX_ATTACHMENTS) {
            throw new AuthorizationException(
                'attachment_max_count',
                [
                    'max' => self::MAX_ATTACHMENTS,
                    'current' => $fileCount,
                ]
            );
        }

        // Validate each file individually
        // Note: Type check removed - files are already typed as UploadedFile in parameter
        foreach ($files as $index => $file) {
            if (! $file->isValid()) {
                throw new AuthorizationException('invalid_file_at_index', ['index' => $index]);
            }
        }
    }

    /**
     * Get all attachment uploads.
     *
     * @return array<int, AttachmentUpload> The uploads array
     */
    public function all(): array
    {
        return $this->uploads;
    }

    /**
     * Get the count of attachments.
     *
     * @return int The number of attachments
     */
    public function count(): int
    {
        return count($this->uploads);
    }

    /**
     * Get the total size of all attachments.
     *
     * @return FileSize The total file size
     */
    public function totalSize(): FileSize
    {
        $totalBytes = array_reduce(
            $this->uploads,
            fn (int $carry, AttachmentUpload $upload): int => $carry + $upload->getFileSize()->bytes(),
            0
        );

        return new FileSize($totalBytes);
    }

    /**
     * Get iterator for foreach support.
     *
     * @return Traversable<int, AttachmentUpload>
     */
    public function getIterator(): Traversable
    {
        yield from $this->uploads;
    }

    /**
     * Check if collection is empty.
     *
     * @return bool True if empty
     */
    public function isEmpty(): bool
    {
        return empty($this->uploads);
    }
}
