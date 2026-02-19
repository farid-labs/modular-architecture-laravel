<?php

namespace Modules\Workspace\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a workspace name.
 * Encapsulates validation and slug generation.
 */
class WorkspaceName
{
    // Original workspace name
    private string $name;

    // URL-friendly slug generated from name
    private string $slug;

    /**
     * Constructor validates the name and generates slug.
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
     * @throws InvalidArgumentException if name is too short or too long
     */
    private function ensureIsValidName(string $name): void
    {
        if (strlen($name) < 3) {
            throw new InvalidArgumentException('Workspace name must be at least 3 characters');
        }

        if (strlen($name) > 100) {
            throw new InvalidArgumentException('Workspace name must not exceed 100 characters');
        }
    }

    /**
     * Generate a URL-friendly slug from the workspace name.
     */
    private function generateSlug(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9-]+/', '-', $name);
        $clean = $clean ?? '';

        return strtolower(trim((string) $clean, '-'));
    }

    // Get original workspace name
    public function getValue(): string
    {
        return $this->name;
    }

    // Get generated slug
    public function getSlug(): string
    {
        return $this->slug;
    }

    // Magic method to convert value object to string
    public function __toString(): string
    {
        return $this->name;
    }
}
