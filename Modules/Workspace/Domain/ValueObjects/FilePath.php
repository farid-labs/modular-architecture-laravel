<?php

namespace Modules\Workspace\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class FilePath
{
    private string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = $value;
    }

    private function ensureIsValid(string $value): void
    {
        if (empty($value) || ! str_starts_with($value, 'task-attachments/')) {
            throw new InvalidArgumentException(__('workspaces.invalid_file_path'));
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
