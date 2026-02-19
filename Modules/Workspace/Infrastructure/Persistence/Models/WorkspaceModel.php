<?php

namespace Modules\Workspace\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Domain\ValueObjects\WorkspaceName;
use Modules\Workspace\Infrastructure\Database\Factories\WorkspaceFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property WorkspaceStatus $status
 * @property int $owner_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<static>
 */
class WorkspaceModel extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'workspaces';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'owner_id',
    ];

    protected $casts = [
        'status' => WorkspaceStatus::class,
    ];

    /**
     * Workspace belongs to an owner (User).
     *
     * @return BelongsTo<UserModel, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'owner_id');
    }

    /**
     * Workspace has many members (Users) via pivot table.
     *
     * @return BelongsToMany<UserModel, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            UserModel::class,
            'workspace_members',
            'workspace_id',
            'user_id'
        )->withPivot('role', 'joined_at')->withTimestamps();
    }

    /**
     * Workspace has many projects.
     *
     * @return HasMany<ProjectModel, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(ProjectModel::class, 'workspace_id');
    }

    /**
     * Get workspace name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get workspace slug.
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Check if workspace is active.
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Update workspace name and slug using value object.
     */
    public function updateName(WorkspaceName $name): void
    {
        $this->name = $name->getValue();
        $this->slug = $name->getSlug();
    }

    /**
     * Activate workspace.
     */
    public function activate(): void
    {
        $this->status = WorkspaceStatus::ACTIVE;
    }

    /**
     * Deactivate workspace.
     */
    public function deactivate(): void
    {
        $this->status = WorkspaceStatus::INACTIVE;
    }

    /**
     * Boot a new factory instance.
     */
    protected static function newFactory(): WorkspaceFactory
    {
        return WorkspaceFactory::new();
    }
}
