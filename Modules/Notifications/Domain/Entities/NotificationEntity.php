<?php

namespace Modules\Notifications\Domain\Entities;

use Carbon\CarbonInterface;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;

/**
 * Entity: NotificationEntity
 *
 * Represents a Notification inside the domain layer.
 *
 * Characteristics:
 * - Immutable (state transitions return new instance).
 * - Encapsulates notification lifecycle logic.
 * - Free from infrastructure concerns (pure domain model).
 *
 * Business Capabilities:
 * - Mark as read / unread
 * - Soft delete
 * - Determine active/read state
 *
 * This entity should not depend on Eloquent or database logic.
 */
final readonly class NotificationEntity
{
    /**
     * Create a new NotificationEntity instance.
     *
     * @param string $id Unique notification identifier (UUID recommended)
     * @param int $recipientId User ID of the notification recipient
     * @param NotificationType $type Notification type
     * @param NotificationPriority $priority Notification priority level
     * @param NotificationCategory $category Notification category
     * @param NotificationContent $content Immutable content value object
     * @param CarbonInterface|null $readAt Timestamp when notification was read
     * @param CarbonInterface|null $deletedAt Soft delete timestamp
     * @param CarbonInterface|null $createdAt Creation timestamp
     * @param array|null $metadata Additional structured metadata
     */
    public function __construct(
        private string $id,
        private int $recipientId,
        private NotificationType $type,
        private NotificationPriority $priority,
        private NotificationCategory $category,
        private NotificationContent $content,
        private ?CarbonInterface $readAt = null,
        private ?CarbonInterface $deletedAt = null,
        private ?CarbonInterface $createdAt = null,
        private ?array $metadata = null
    ) {}

    /** Get notification unique identifier. */
    public function getId(): string
    {
        return $this->id;
    }

    /** Get recipient user ID. */
    public function getRecipientId(): int
    {
        return $this->recipientId;
    }

    /** Get notification type. */
    public function getType(): NotificationType
    {
        return $this->type;
    }

    /** Get notification priority. */
    public function getPriority(): NotificationPriority
    {
        return $this->priority;
    }

    /** Get notification category. */
    public function getCategory(): NotificationCategory
    {
        return $this->category;
    }

    /** Get immutable notification content. */
    public function getContent(): NotificationContent
    {
        return $this->content;
    }

    /** Get read timestamp. */
    public function getReadAt(): ?CarbonInterface
    {
        return $this->readAt;
    }

    /** Get soft delete timestamp. */
    public function getDeletedAt(): ?CarbonInterface
    {
        return $this->deletedAt;
    }

    /** Get creation timestamp. */
    public function getCreatedAt(): ?CarbonInterface
    {
        return $this->createdAt;
    }

    /** Get optional metadata payload. */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Determine whether the notification has been read.
     */
    public function isRead(): bool
    {
        return $this->readAt !== null;
    }

    /**
     * Determine whether the notification has been soft deleted.
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    /**
     * Determine whether the notification is active (not deleted).
     */
    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    /**
     * Mark notification as read.
     *
     * Returns a new immutable instance with updated state.
     */
    public function markAsRead(): self
    {
        if ($this->isRead()) {
            return $this; // Prevent unnecessary state mutation
        }

        return new self(
            $this->id,
            $this->recipientId,
            $this->type,
            $this->priority,
            $this->category,
            $this->content,
            now(),
            $this->deletedAt,
            $this->createdAt,
            $this->metadata
        );
    }

    /**
     * Mark notification as unread.
     *
     * Returns a new immutable instance.
     */
    public function markAsUnread(): self
    {
        if (! $this->isRead()) {
            return $this;
        }

        return new self(
            $this->id,
            $this->recipientId,
            $this->type,
            $this->priority,
            $this->category,
            $this->content,
            null,
            $this->deletedAt,
            $this->createdAt,
            $this->metadata
        );
    }

    /**
     * Soft delete the notification.
     *
     * Returns a new immutable instance marked as deleted.
     */
    public function softDelete(): self
    {
        if ($this->isDeleted()) {
            return $this;
        }

        return new self(
            $this->id,
            $this->recipientId,
            $this->type,
            $this->priority,
            $this->category,
            $this->content,
            $this->readAt,
            now(),
            $this->createdAt,
            $this->metadata
        );
    }

    /**
     * Convert entity into a serializable array.
     *
     * Intended for:
     * - API responses
     * - Infrastructure persistence mapping
     * - Logging
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'recipient_id' => $this->recipientId,
            'type'         => $this->type->value,
            'priority'     => $this->priority->value,
            'category'     => $this->category->value,
            'content'      => $this->content->toArray(),
            'read_at'      => $this->readAt?->toIso8601String(),
            'deleted_at'   => $this->deletedAt?->toIso8601String(),
            'created_at'   => $this->createdAt?->toIso8601String(),
            'metadata'     => $this->metadata,
            'is_read'      => $this->isRead(),
            'is_active'    => $this->isActive(),
        ];
    }
}
