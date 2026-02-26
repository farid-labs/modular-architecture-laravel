<?php

namespace Modules\Notifications\Application\DTOs;

use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * DTO: SendNotificationDTO
 *
 * Represents the data required to dispatch a notification
 * from the Application layer.
 *
 * Responsibilities:
 * - Transport structured notification data between layers.
 * - Ensure strong typing using domain enums.
 * - Provide convenient factory construction.
 *
 * This DTO should remain free of business logic.
 */
class SendNotificationDTO extends DataTransferObject
{
    /** Recipient user identifier */
    public int $recipientId;

    /** Notification type */
    public NotificationType $type;

    /** Notification priority level */
    public NotificationPriority $priority;

    /** Notification category */
    public NotificationCategory $category;

    /** Notification title */
    public string $title;

    /** Notification message body */
    public string $message;

    /**
     * Delivery channels.
     *
     * @var NotificationChannel[]
     */
    public array $channels;

    /** Optional action URL */
    public ?string $actionUrl = null;

    /** Optional action label */
    public ?string $actionLabel = null;

    /**
     * Optional structured metadata payload.
     *
     * @var array<string, mixed>|null
     */
    public ?array $metadata = null;

    /**
     * Factory method for creating a user-targeted notification DTO.
     *
     * Provides sensible defaults for:
     * - Channel (DATABASE)
     * - Priority (MEDIUM)
     * - Category (SYSTEM)
     *
     * @param  array<NotificationChannel>  $channels
     * @param  array<string, mixed>|null  $metadata
     */
    public static function forUser(
        int $userId,
        NotificationType $type,
        string $title,
        string $message,
        array $channels = [NotificationChannel::DATABASE],
        NotificationPriority $priority = NotificationPriority::MEDIUM,
        NotificationCategory $category = NotificationCategory::SYSTEM,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?array $metadata = null
    ): self {
        return new self([
            'recipientId' => $userId,
            'type' => $type,
            'priority' => $priority,
            'category' => $category,
            'title' => $title,
            'message' => $message,
            'channels' => $channels,
            'actionUrl' => $actionUrl,
            'actionLabel' => $actionLabel,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Convert DTO into array representation.
     *
     * Useful for:
     * - Job dispatching
     * - Logging
     * - Infrastructure adapters
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'recipient_id' => $this->recipientId,
            'type' => $this->type->value,
            'priority' => $this->priority->value,
            'category' => $this->category->value,
            'title' => $this->title,
            'message' => $this->message,
            'channels' => array_map(
                fn (NotificationChannel $channel) => $channel->value,
                $this->channels
            ),
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'metadata' => $this->metadata,
        ];
    }
}
