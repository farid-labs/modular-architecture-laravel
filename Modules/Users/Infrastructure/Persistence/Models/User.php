<?php

namespace Modules\Users\Infrastructure\Persistence\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Domain\ValueObjects\Name;
use Illuminate\Database\Eloquent\Factories\HasFactory;


/**
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 *
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    /** @phpstan-ignore-next-line */
    use HasFactory;

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

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'password' => 'hashed',
        ];
    }

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
