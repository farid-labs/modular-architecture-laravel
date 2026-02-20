<?php

namespace Modules\Workspace\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class CommentContent
{
    private string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = trim($value);
    }

    private function ensureIsValid(string $value): void
    {
        if (mb_strlen($value) < 3) {
            throw new InvalidArgumentException(__('workspaces.comment_min_length'));
        }
        if (mb_strlen($value) > 2000) {
            throw new InvalidArgumentException(__('workspaces.comment_max_length'));
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
