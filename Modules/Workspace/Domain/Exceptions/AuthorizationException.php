<?php

namespace Modules\Workspace\Domain\Exceptions;

use InvalidArgumentException;

class AuthorizationException extends InvalidArgumentException
{
    /**
     * Create a new authorization exception instance.
     *
     * @param  string  $errorCode  The error code key for translation
     * @param  array<string, mixed>  $params  Parameters to pass to the translation function
     * @param  string  $message  Optional custom message
     */
    public function __construct(
        public string $errorCode,
        public array $params = [],
        string $message = ''
    ) {
        parent::__construct($message ?: __("workspaces.{$errorCode}", $params));
    }
}
