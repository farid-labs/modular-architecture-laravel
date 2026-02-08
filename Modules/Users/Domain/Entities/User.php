<?php

namespace Modules\Users\Domain\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Domain\ValueObjects\Name;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get user's full name
     */
    public function getFullName(): string
    {
        return $this->name;
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Update user email
     */
    public function updateEmail(Email $email): void
    {
        $this->email = $email->getValue();
        $this->email_verified_at = null;
    }

    /**
     * Update user name
     */
    public function updateName(Name $name): void
    {
        $this->name = $name->getValue();
    }
}