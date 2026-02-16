<?php

namespace Modules\Users\Domain\Entities;

use Carbon\CarbonInterface;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Domain\ValueObjects\Name;

/**
 * Pure domain entity representing a user (WITHOUT sensitive data)
 * Immutable value object with full encapsulation and business logic
 *
 * SECURITY NOTE: Password is NEVER stored in domain entity.
 * Password handling belongs to infrastructure layer (UserModel) only.
 */
class UserEntity
{
    public function __construct(
        private readonly int $id,
        private readonly Name $name,
        private readonly Email $email,
        private readonly ?CarbonInterface $emailVerifiedAt,
        private readonly CarbonInterface $createdAt,
        private readonly CarbonInterface $updatedAt,
        private readonly bool $isAdmin = false
    ) {
        // Constructor body is EMPTY - all properties are promoted
        // NO password handling here (security best practice)
        // NO active flag handling here (derived from emailVerifiedAt)
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * User is active ONLY if email is verified (business rule)
     * This is a DERIVED property, not stored state
     */
    public function isActive(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function getEmailVerifiedAt(): ?CarbonInterface
    {
        return $this->emailVerifiedAt;
    }

    public function getCreatedAt(): CarbonInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): CarbonInterface
    {
        return $this->updatedAt;
    }

    public function updateName(Name $newName): self
    {
        // Note: Slug generation should happen in Value Object or Service layer
        return new self(
            $this->id,
            $newName,
            $this->email,
            $this->emailVerifiedAt,
            $this->createdAt,
            $this->updatedAt,
            $this->isAdmin
        );
    }

    public function verifyEmail(): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->email,
            now(),
            $this->createdAt,
            now(),
            $this->isAdmin
        );
    }

    public function promoteToAdmin(): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->email,
            $this->emailVerifiedAt,
            $this->createdAt,
            now(),
            true
        );
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     email_verified_at: string|null,
     *     is_admin: bool,
     *     is_active: bool,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name->getValue(),
            'email' => $this->email->getValue(),
            'email_verified_at' => $this->emailVerifiedAt?->toIso8601String(),
            'is_admin' => $this->isAdmin,
            'is_active' => $this->isActive(), // Derived property
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
        ];
    }
}
