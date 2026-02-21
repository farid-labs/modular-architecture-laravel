<?php

namespace Modules\Workspace\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing task comment content.
 *
 * Encapsulates validation rules for comment text:
 * - Minimum length: 3 characters
 * - Maximum length: 2000 characters
 * - Automatically trims whitespace
 *
 * Ensures comment content integrity across the domain layer.
 * Immutable by design (readonly class).
 */
final readonly class CommentContent
{
    /**
     * The validated comment content.
     */
    private string $value;

    /**
     * Create a new CommentContent value object.
     *
     * Validates the content before instantiation.
     *
     * @param  string  $value  The raw comment content
     *
     * @throws InvalidArgumentException If content length is invalid
     */
    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = trim($value);
    }

    /**
     * Validate the comment content.
     *
     * Checks minimum and maximum length constraints.
     *
     * @param  string  $value  The content to validate
     *
     * @throws InvalidArgumentException If validation fails
     */
    private function ensureIsValid(string $value): void
    {
        // Enforce minimum length requirement
        if (mb_strlen($value) < 3) {
            throw new InvalidArgumentException(__('workspaces.comment_min_length'));
        }

        // Enforce maximum length requirement
        if (mb_strlen($value) > 2000) {
            throw new InvalidArgumentException(__('workspaces.comment_max_length'));
        }
    }

    /**
     * Get the validated comment content.
     *
     * @return string The trimmed comment content
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Convert value object to string.
     *
     * @return string The comment content
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
