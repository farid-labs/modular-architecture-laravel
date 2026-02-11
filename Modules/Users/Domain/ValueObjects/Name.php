<?php

namespace Modules\Users\Domain\ValueObjects;

use InvalidArgumentException;

class Name
{
    private string $name;

    public function __construct(string $name)
    {
        $this->ensureIsValidName($name);
        $this->name = $name;
    }

    private function ensureIsValidName(string $name): void
    {
        if (strlen($name) < 2) {
            throw new InvalidArgumentException('Name must be at least 2 characters');
        }

        if (strlen($name) > 100) {
            throw new InvalidArgumentException('Name must not exceed 100 characters');
        }
    }

    public function getValue(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
