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

    protected $fillable = [
        'name',
        'description',
        'workspace_id',
        'status',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
    ];

    /**
     * @return BelongsTo<WorkspaceModel, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(WorkspaceModel::class, 'workspace_id');
    }

    /**
     * @return HasMany<TaskModel, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(TaskModel::class, 'project_id');
    }

    /**
     * @return BelongsTo<WorkspaceModel, $this>
     */
    public function projectModel(): BelongsTo
    {
        return $this->belongsTo(WorkspaceModel::class, 'workspace_id');
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
