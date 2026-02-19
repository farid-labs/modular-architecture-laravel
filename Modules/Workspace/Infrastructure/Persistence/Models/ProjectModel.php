<?php

namespace Modules\Workspace\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Modules\Workspace\Infrastructure\Database\Factories\ProjectFactory;

/**
 * Eloquent model representing a Project.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $workspace_id
 * @property ProjectStatus $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<static>
 */
class ProjectModel extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'projects';

    // Mass assignable attributes
    protected $fillable = [
        'name',
        'description',
        'workspace_id',
        'status',
    ];

    // Cast status to ProjectStatus enum
    protected $casts = [
        'status' => ProjectStatus::class,
    ];

    /**
     * Relationship: Project belongs to a Workspace.
     *
     * @return BelongsTo<WorkspaceModel, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(WorkspaceModel::class, 'workspace_id');
    }

    /**
     * Relationship: Project has many Tasks.
     *
     * @return HasMany<TaskModel, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(TaskModel::class, 'project_id');
    }

    /**
     * Boot a new factory instance for this model.
     */
    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
