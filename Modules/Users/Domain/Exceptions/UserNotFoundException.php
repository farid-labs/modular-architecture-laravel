<?php

namespace Modules\Users\Domain\Exceptions;

use Exception;

class UserNotFoundException extends Exception
{
    private function __construct(string $message)
    {
        parent::__construct($message, 404);
    }

    public static function withId(int $id): self
    {
        return new self("User with ID {$id} not found");
    }
}
