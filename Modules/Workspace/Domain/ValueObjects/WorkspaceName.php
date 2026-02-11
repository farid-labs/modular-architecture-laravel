<?php

namespace Modules\Workspace\Domain\ValueObjects;

use InvalidArgumentException;

class WorkspaceName
{
    private string $name;

    private string $slug;

    public function __construct(string $name)
    {
        $this->ensureIsValidName($name);
        $this->name = $name;
        $this->slug = $this->generateSlug($name);
    }

    private function ensureIsValidName(string $name): void
    {
        if (strlen($name) < 3) {
            throw new InvalidArgumentException('Workspace name must be at least 3 characters');
        }

        if (strlen($name) > 100) {
            throw new InvalidArgumentException('Workspace name must not exceed 100 characters');
        }
    }

    private function generateSlug(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9-]+/', '-', $name);
        // ensure $clean is a string (avoid analyzer warning and possible null at runtime)
        $clean = $clean ?? '';
        return strtolower(trim((string)$clean, '-'));
    }

    public function getValue(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
