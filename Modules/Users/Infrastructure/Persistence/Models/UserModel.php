<?php

namespace Modules\Users\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Users\Infrastructure\Database\Factories\UserFactory;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property bool $is_admin
 *
 * @mixin \Eloquent
 */
class UserModel extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable, SoftDeletes;

    /** @phpstan-ignore-next-line */
    use HasFactory;

    protected $table = 'users';  // Explicitly set table name to match migration

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'email_verified_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
        'password' => 'hashed',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * Determine if the user is an administrator.
     */
    public function getIsAdminAttribute(): bool
    {
        return (bool) ($this->attributes['is_admin'] ?? false);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<WorkspaceModel, $this>
     */
    public function workspaces()
    {
        return $this->belongsToMany(WorkspaceModel::class, 'workspace_members', 'user_id', 'workspace_id')
            ->withPivot('role', 'joined_at');
    }
}
