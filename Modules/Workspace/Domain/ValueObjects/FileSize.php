<?php

namespace Modules\Workspace\Domain\ValueObjects;

use Modules\Workspace\Domain\Exceptions\AuthorizationException;

/**
 * Value object representing a file size in bytes.
 *
 * Encapsulates validation rules for file sizes:
 * - Cannot be negative
 * - Maximum size: 10MB (10,485,760 bytes)
 *
 * Ensures file size integrity across the domain layer.
 * Immutable by design (readonly class).
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
final readonly class FileSize
{
    /**
     * Maximum allowed file size in bytes (10MB).
     */
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10MB

    /**
     * The validated file size in bytes.
     */
    private int $bytes;

    /**
     * Create a new FileSize value object.
     *
     * Validates the file size before instantiation.
     *
     * @param  int  $bytes  The raw file size in bytes
     *
     * @throws AuthorizationException If file size is invalid
     */
    public function __construct(int $bytes)
    {
        $this->ensureIsValid($bytes);
        $this->bytes = $bytes;
    }

    /**
     * Validate the file size.
     *
     * Checks for negative values and maximum size constraints.
     *
     * @param  int  $bytes  The file size to validate
     *
     * @throws AuthorizationException If validation fails
     */
    private function ensureIsValid(int $bytes): void
    {
        if ($bytes < 0) {
            throw new AuthorizationException('file_size_negative');
        }

        if ($bytes > self::MAX_SIZE_BYTES) {
            throw new AuthorizationException(
                'file_size_exceeds_limit',
                [
                    'max' => $this->formatBytes(self::MAX_SIZE_BYTES),
                    'current' => $this->formatBytes($bytes),
                ]
            );
        }
    }

    /**
     * Get the file size in bytes.
     *
     * @return int The file size in bytes
     */
    public function bytes(): int
    {
        return $this->bytes;
    }

    /**
     * Get the file size in human-readable format.
     *
     * @return string The formatted file size (e.g., "10 MB")
     */
    public function formatted(): string
    {
        return $this->formatBytes($this->bytes);
    }

    /**
     * Check if this file size exceeds another.
     *
     * @param  FileSize  $other  The other file size to compare
     * @return bool True if this size is greater than the other
     */
    public function exceeds(FileSize $other): bool
    {
        return $this->bytes > $other->bytes;
    }

    /**
     * Format bytes to human-readable string.
     *
     * @param  int  $bytes  The bytes to format
     * @return string The formatted string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = (int) floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Convert value object to string.
     *
     * @return string The formatted file size
     */
    public function __toString(): string
    {
        return $this->formatted();
    }
}
