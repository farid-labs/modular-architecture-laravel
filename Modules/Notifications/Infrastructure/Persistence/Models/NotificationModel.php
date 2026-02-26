<?php

namespace Modules\Notifications\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Notifications\Infrastructure\Database\Factories\NotificationFactory;

/**
 * Class NotificationModel
 *
 * Eloquent model representing notifications stored in the database.
 *
 * @property string $id
 * @property string $type
 * @property string $priority
 * @property string $category
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array<string, mixed> $data
 * @property \Carbon\Carbon|null $read_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string $locale
 * @property array<string> $channels
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class NotificationModel extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory, SoftDeletes;

    /** @var string Database table name */
    protected $table = 'notifications';

    /** @var list<string> Mass assignable fields */
    protected $fillable = [
        'id',
        'type',
        'priority',
        'category',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'deleted_at',
        'locale',
        'channels',
    ];

    /** @var array<string, string> Cast attributes to native types */
    protected $casts = [
        'data' => 'array',
        'channels' => 'array',
        'read_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** @var string Primary key type */
    protected $keyType = 'string';

    /** @var bool Indicates if the IDs are incrementing */
    public $incrementing = false;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }

    /**
     * Get the owning notifiable entity (User, Workspace, etc.)
     *
     * @return MorphTo<Model, $this>
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if the notification has been read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null; // ← Returns true if readAt has value
    }

    /**
     * Check if the notification is unread.
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Mark the notification as read.
     * Updates the read_at timestamp and persists to database.
     */
    public function markAsRead(): void
    {
        $this->read_at = now();
        $this->save();
    }

    /**
     * Mark the notification as unread.
     * Clears the read_at timestamp and persists to database.
     */
    public function markAsUnread(): void
    {
        $this->read_at = null;
        $this->save();
    }
}
