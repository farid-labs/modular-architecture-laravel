<?php

namespace Modules\Workspace\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing an attachment file name.
 *
 * Encapsulates validation rules for file names:
 * - Cannot be empty
 * - Maximum length: 255 characters
 * - Preserves original client-side file name
 *
 * Ensures file name integrity across the domain layer.
 * Immutable by design (readonly class).
 */
final readonly class FileName
{
    /**
     * The validated file name.
     */
    private string $value;

    /**
     * Create a new FileName value object.
     *
     * Validates the file name before instantiation.
     *
     * @param  string  $value  The raw file name
     *
     * @throws InvalidArgumentException If file name is invalid
     */
    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = $value;
    }

    /**
     * Validate the file name.
     *
     * Checks for empty value and maximum length constraints.
     *
     * @param  string  $value  The file name to validate
     *
     * @throws InvalidArgumentException If validation fails
     */
    private function ensureIsValid(string $value): void
    {
        // Prevent empty file names
        if (empty($value) || mb_strlen($value) > 255) {
            throw new InvalidArgumentException(__('workspaces.invalid_file_name'));
        }
    }

    /**
     * Get the validated file name.
     *
     * @return string The file name
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Convert value object to string.
     *
     * @return string The file name
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
