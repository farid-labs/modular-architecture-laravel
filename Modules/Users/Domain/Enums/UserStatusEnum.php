<?php

namespace Modules\Users\Domain\Enums;

/**
 * Enum UserStatusEnum
 *
 * Represents the possible lifecycle states of a User within the system.
 *
 * This enum belongs to the Domain layer and should not contain
 * any infrastructure or presentation logic.
 *
 * Available statuses:
 * - ACTIVE:    User account is active and can access the system.
 * - INACTIVE:  User account is disabled but not banned.
 * - BANNED:    User account is permanently or temporarily blocked.
 * - PENDING:   User account is awaiting approval or verification.
 *
 * @package Modules\Users\Domain\Enums
 */
enum UserStatusEnum: string
{
    /**
     * User account is fully active.
     */
    case ACTIVE = 'active';

    /**
     * User account is inactive (soft-disabled).
     */
    case INACTIVE = 'inactive';

    /**
     * User account is banned from the system.
     */
    case BANNED = 'banned';

    /**
     * User account is pending approval or verification.
     */
    case PENDING = 'pending';

    /**
     * Get the localized human-readable label for the status.
     *
     * This method delegates translation to Laravel's localization system.
     * Translation key format: users.statuses.{value}
     *
     * Example:
     * users.statuses.active => "Active"
     *
     * @return string
     */
    public function label(): string
    {
        return trans("users.statuses.{$this->value}");
    }
}
