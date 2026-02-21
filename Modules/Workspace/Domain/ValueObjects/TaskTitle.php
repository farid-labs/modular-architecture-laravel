<?php

namespace Modules\Workspace\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a task title.
 *
 * Encapsulates validation rules for task titles:
 * - Minimum length: 3 characters
 * - Maximum length: 255 characters
 * - Automatically trims whitespace
 *
 * Ensures task title integrity across the domain layer.
 * Immutable by design (readonly class).
 */
final readonly class TaskTitle
{
    /**
     * The validated task title.
     */
    private string $value;

    /**
     * Create a new TaskTitle value object.
     *
     * Validates the title before instantiation.
     *
     * @param  string  $value  The raw task title
     *
     * @throws InvalidArgumentException If title length is invalid
     */
    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = trim($value);
    }

    /**
     * Validate the task title.
     *
     * Checks minimum and maximum length constraints.
     *
     * @param  string  $value  The title to validate
     *
     * @throws InvalidArgumentException If validation fails
     */
    private function ensureIsValid(string $value): void
    {
        // Enforce minimum length requirement
        if (mb_strlen($value) < 3) {
            throw new InvalidArgumentException(__('workspaces.task_title_min_length'));
        }

        // Enforce maximum length requirement
        if (mb_strlen($value) > 255) {
            throw new InvalidArgumentException(__('workspaces.task_title_max_length'));
        }
    }

    /**
     * Get the validated task title.
     *
     * @return string The trimmed task title
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Convert value object to string.
     *
     * @return string The task title
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
