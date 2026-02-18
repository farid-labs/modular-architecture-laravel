<?php

namespace Modules\Workspace\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Database\Factories\TaskAttachmentFactory;

/**
 * @property int $id
 * @property int $task_id
 * @property string $file_name
 * @property string $file_path
 * @property int $file_size
 * @property string $file_type
 * @property int $uploaded_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TaskAttachmentModel extends Model
{
    /** @use HasFactory<TaskAttachmentFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'task_attachments';

    protected $fillable = [
        'task_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'uploaded_by',
    ];

    /**
     * @return BelongsTo<TaskModel, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'uploaded_by');
    }

    public function getFileUrl(): string
    {
        return Storage::url($this->file_path);
    }

    public function isImage(): bool
    {
        return in_array($this->file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function isDocument(): bool
    {
        return in_array($this->file_type, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    protected static function newFactory(): TaskAttachmentFactory
    {
        return TaskAttachmentFactory::new();
    }
}
