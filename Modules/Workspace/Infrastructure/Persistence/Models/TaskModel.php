<?php

namespace Modules\Workspace\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Database\Factories\TaskFactory;

/**
 * Task persistence model.
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $project_id
 * @property int|null $assigned_to
 * @property \Modules\Workspace\Domain\Enums\TaskStatus $status
 * @property \Modules\Workspace\Domain\Enums\TaskPriority $priority
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TaskModel extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'tasks';

    protected $fillable = [
        'title',
        'description',
        'project_id',
        'assigned_to',
        'status',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'immutable_datetime',
        'status' => \Modules\Workspace\Domain\Enums\TaskStatus::class,
        'priority' => \Modules\Workspace\Domain\Enums\TaskPriority::class,
    ];

    /**
     * Get the project that owns the task.
     *
     * @return BelongsTo<ProjectModel, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectModel::class, 'project_id');
    }

    /**
     * Get the user assigned to the task.
     *
     * @return BelongsTo<UserModel, $this>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'assigned_to');
    }

    /**
     * Get the comments for the task.
     *
     * @return HasMany<TaskCommentModel, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskCommentModel::class, 'task_id');
    }

    /**
     * Get the attachments for the task.
     *
     * @return HasMany<TaskAttachmentModel, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachmentModel::class, 'task_id');
    }

    /**
     * Determine if the task is active (pending or in progress).
     */
    public function isActive(): bool
    {
        return $this->status->value === 'pending' || $this->status->value === 'in_progress';
    }

    /**
     * Determine if the task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status->value === 'completed';
    }

    /**
     * Determine if the task is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && ! $this->isCompleted();
    }
}
