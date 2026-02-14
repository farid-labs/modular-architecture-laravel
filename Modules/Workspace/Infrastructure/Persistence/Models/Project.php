<?php

namespace Modules\Workspace\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Modules\Workspace\Infrastructure\Database\Factories\ProjectFactory;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $workspace_id
 * @property ProjectStatus $status
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'workspace_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return BelongsToMany<UserModel, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            UserModel::class,
            'project_members',
            'project_id',
            'user_id'
        )->withPivot('role')->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status->value === 'active';
    }
}
