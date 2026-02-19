<?php

namespace Modules\Workspace\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Database\Factories\TaskCommentFactory;

/**
 * Eloquent model representing a Task Comment.
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $comment
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TaskCommentModel extends Model
{
    /** @use HasFactory<TaskCommentFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'task_comments';

    // Mass assignable attributes
    protected $fillable = [
        'task_id',
        'user_id',
        'comment',
    ];

    /**
     * Relationship: Comment belongs to a Task.
     *
     * @return BelongsTo<TaskModel, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }

    /**
     * Relationship: Comment belongs to a User.
     *
     * @return BelongsTo<UserModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Boot a new factory instance for this model.
     */
    protected static function newFactory(): TaskCommentFactory
    {
        return TaskCommentFactory::new();
    }
}
