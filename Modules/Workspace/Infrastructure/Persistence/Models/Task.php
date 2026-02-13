<?php

namespace Modules\Workspace\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Users\Infrastructure\Persistence\Models\User;
use Modules\Workspace\Infrastructure\Database\Factories\TaskFactory;

/**
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
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use SoftDeletes;

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
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * @return HasMany<TaskAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function isActive(): bool
    {
        return $this->status->value === 'pending' || $this->status->value === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status->value === 'completed';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && ! $this->isCompleted();
    }
}
