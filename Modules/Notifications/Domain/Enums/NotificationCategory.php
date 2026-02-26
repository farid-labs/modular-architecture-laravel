<?php

namespace Modules\Notifications\Domain\Enums;

/**
 * Enum: NotificationCategory
 *
 * Represents the category of a notification.
 *
 * Responsibilities:
 * - Defines available notification group types.
 * - Supports filtering and grouping logic.
 * - Provides localized labels for presentation layer.
 *
 * This enum centralizes category definitions
 * to prevent the usage of magic strings
 * across the application.
 */
enum NotificationCategory: string
{
    case SYSTEM = 'system';
    case USER = 'user';
    case WORKSPACE = 'workspace';
    case PROJECT = 'project';
    case TASK = 'task';
    case SECURITY = 'security';

    /**
     * Get the localized label of the notification category.
     *
     * Labels are retrieved from language files
     * to ensure proper internationalization support.
     *
     * Expected lang file:
     * lang/en/notification.php
     */
    public function label(): string
    {
        return match ($this) {
            self::SYSTEM => __('notification.category.system'),
            self::USER => __('notification.category.user'),
            self::WORKSPACE => __('notification.category.workspace'),
            self::PROJECT => __('notification.category.project'),
            self::TASK => __('notification.category.task'),
            self::SECURITY => __('notification.category.security'),
        };
    }
}
