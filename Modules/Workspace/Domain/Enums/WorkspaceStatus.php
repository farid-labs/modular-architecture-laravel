<?php

namespace Modules\Workspace\Domain\Enums;

/**
 * Enum representing the status of a workspace.
 * Provides helper methods to check specific workspace states.
 */
enum WorkspaceStatus: string
{
    // Workspace is active
    case ACTIVE = 'active';

    // Workspace is inactive
    case INACTIVE = 'inactive';

    // Workspace is suspended
    case SUSPENDED = 'suspended';

    // Check if workspace is active
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    // Check if workspace is inactive
    public function isInactive(): bool
    {
        return $this === self::INACTIVE;
    }

    // Check if workspace is suspended
    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }
}
