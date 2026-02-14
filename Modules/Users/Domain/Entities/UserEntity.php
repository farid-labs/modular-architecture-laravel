<?php

namespace Modules\Users\Domain\Entities;

use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Domain\ValueObjects\Name;

class UserEntity
{
    private int $id;

    private Name $name;

    private Email $email;

    private ?string $password;

    private bool $isAdmin;

    private ?\Carbon\CarbonImmutable $emailVerifiedAt;

    private ?\Carbon\CarbonImmutable $createdAt;

    private ?\Carbon\CarbonImmutable $updatedAt;

    public function __construct(
        int $id,
        Name $name,
        Email $email,
        ?string $password = null,
        bool $isAdmin = false,
        ?\Carbon\CarbonImmutable $emailVerifiedAt = null,
        ?\Carbon\CarbonImmutable $createdAt = null,
        ?\Carbon\CarbonImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->isAdmin = $isAdmin;
        $this->emailVerifiedAt = $emailVerifiedAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->name->getValue();
    }

    public function isActive(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->emailVerifiedAt = null;
    }

    public function updateName(Name $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getEmailVerifiedAt(): ?\Carbon\CarbonImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function getCreatedAt(): ?\Carbon\CarbonImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\Carbon\CarbonImmutable
    {
        return $this->updatedAt;
    }
}
