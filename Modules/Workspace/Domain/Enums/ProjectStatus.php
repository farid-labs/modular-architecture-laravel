<?php

namespace Modules\Workspace\Domain\Enums;

/**
 * Enum representing the status of a project.
 * Provides helper methods to check specific states.
 */
enum ProjectStatus: string
{
    // Project is currently active
    case ACTIVE = 'active';

    // Project has been completed
    case COMPLETED = 'completed';

    // Project has been archived
    case ARCHIVED = 'archived';

    // Check if project is active
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    // Check if project is completed
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    // Check if project is archived
    public function isArchived(): bool
    {
        return $this === self::ARCHIVED;
    }
}
