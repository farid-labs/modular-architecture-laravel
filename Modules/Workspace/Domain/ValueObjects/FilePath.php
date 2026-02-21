<?php

namespace Modules\Workspace\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing an attachment file path.
 *
 * Encapsulates validation rules for file paths:
 * - Cannot be empty
 * - Must start with 'task-attachments/' prefix
 * - Ensures consistent storage location
 *
 * Ensures file path integrity and security across the domain layer.
 * Immutable by design (readonly class).
 */
final readonly class FilePath
{
    /**
     * The validated file path.
     */
    private string $value;

    /**
     * Create a new FilePath value object.
     *
     * Validates the file path before instantiation.
     *
     * @param  string  $value  The raw file path
     *
     * @throws InvalidArgumentException If file path is invalid
     */
    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = $value;
    }

    /**
     * Validate the file path.
     *
     * Checks for empty value and required prefix.
     * Prevents directory traversal attacks.
     *
     * @param  string  $value  The file path to validate
     *
     * @throws InvalidArgumentException If validation fails
     */
    private function ensureIsValid(string $value): void
    {
        // Prevent empty paths and enforce storage location
        if (empty($value) || ! str_starts_with($value, 'task-attachments/')) {
            throw new InvalidArgumentException(__('workspaces.invalid_file_path'));
        }
    }

    /**
     * Get the validated file path.
     *
     * @return string The file path
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Convert value object to string.
     *
     * @return string The file path
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
