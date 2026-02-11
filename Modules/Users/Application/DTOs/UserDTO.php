<?php

namespace Modules\Users\Application\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

class UserDTO extends DataTransferObject
{
    public string $name;
    public string $email;
    public ?string $password = null;
    public ?string $email_verified_at = null;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'email_verified_at' => $this->email_verified_at,
        ];
    }
}
