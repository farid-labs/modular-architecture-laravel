<?php

namespace Modules\Notifications\Domain\Enums;

/**
 * Enum: NotificationPriority
 *
 * Represents the priority level of a notification.
 *
 * Responsibilities:
 * - Defines available priority levels.
 * - Determines UI badge color representation.
 * - Decides whether push notification should be sent.
 * - Provides localized label for display purposes.
 *
 * This enum should be used across the domain layer
 * to maintain consistency and avoid magic strings.
 */
enum NotificationPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    /**
     * Get the badge color associated with the priority level.
     *
     * This value is typically used in UI components
     * to visually distinguish notification urgency.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::URGENT => 'red',
            self::HIGH => 'orange',
            self::MEDIUM => 'yellow',
            self::LOW => 'green',
        };
    }

    /**
     * Determine whether this priority level
     * requires sending a push notification.
     *
     * Business Rule:
     * Only HIGH and URGENT notifications trigger push delivery.
     */
    public function shouldSendPush(): bool
    {
        return in_array($this, [self::HIGH, self::URGENT], true);
    }

    /**
     * Get the localized label of the priority.
     *
     * Labels are retrieved from the language files
     * to support internationalization.
     *
     * Expected lang file:
     * lang/en/notification.php
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW => __('notification.priority.low'),
            self::MEDIUM => __('notification.priority.medium'),
            self::HIGH => __('notification.priority.high'),
            self::URGENT => __('notification.priority.urgent'),
        };
    }
}
