<?php

namespace Modules\Workspace\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class FileName
{
    private string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = $value;
    }

    private function ensureIsValid(string $value): void
    {
        if (empty($value) || mb_strlen($value) > 255) {
            throw new InvalidArgumentException(__('workspaces.invalid_file_name'));
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
