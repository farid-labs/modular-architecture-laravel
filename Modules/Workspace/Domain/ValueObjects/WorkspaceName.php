<?php

namespace Modules\Workspace\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a workspace name.
 *
 * Encapsulates validation and slug generation:
 * - Minimum length: 3 characters
 * - Maximum length: 100 characters
 * - Auto-generates URL-friendly slug
 * - Ensures naming consistency across workspaces
 *
 * Provides immutable workspace name representation.
 * Used during workspace creation and updates.
 */
class WorkspaceName
{
    /**
     * The original workspace name.
     */
    private string $name;

    /**
     * The URL-friendly slug generated from the name.
     */
    private string $slug;

    /**
     * Create a new WorkspaceName value object.
     *
     * Validates the name and generates slug automatically.
     *
     * @param  string  $name  The workspace name
     *
     * @throws InvalidArgumentException If name length is invalid
     */
    public function __construct(string $name)
    {
        $this->ensureIsValidName($name);
        $this->name = $name;
        $this->slug = $this->generateSlug($name);
    }

    /**
     * Validate workspace name length.
     *
     * Enforces minimum and maximum character constraints.
     *
     * @param  string  $name  The name to validate
     *
     * @throws InvalidArgumentException If name is too short or too long
     */
    private function ensureIsValidName(string $name): void
    {
        // Enforce minimum length requirement
        if (strlen($name) < 3) {
            throw new InvalidArgumentException('Workspace name must be at least 3 characters');
        }

        // Enforce maximum length requirement
        if (strlen($name) > 100) {
            throw new InvalidArgumentException('Workspace name must not exceed 100 characters');
        }
    }

    /**
     * Generate a URL-friendly slug from the workspace name.
     *
     * Converts to lowercase, replaces special characters with hyphens.
     *
     * @param  string  $name  The workspace name
     * @return string The generated slug
     */
    private function generateSlug(string $name): string
    {
        // Replace non-alphanumeric characters with hyphens
        $clean = preg_replace('/[^A-Za-z0-9-]+/', '-', $name);
        $clean = $clean ?? '';

        // Convert to lowercase and trim hyphens
        return strtolower(trim((string) $clean, '-'));
    }

    /**
     * Get the original workspace name.
     *
     * @return string The workspace name
     */
    public function getValue(): string
    {
        return $this->name;
    }

    /**
     * Get the generated slug.
     *
     * @return string The URL-friendly slug
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Convert value object to string.
     *
     * Returns the original workspace name.
     *
     * @return string The workspace name
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
