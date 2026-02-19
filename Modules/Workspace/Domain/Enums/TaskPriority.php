<?php

namespace Modules\Workspace\Domain\Enums;

/**
 * Enum representing the priority levels of a task.
 * Provides helper methods to check specific priority levels.
 */
enum TaskPriority: string
{
    // Low priority
    case LOW = 'low';

    // Medium priority
    case MEDIUM = 'medium';

    // High priority
    case HIGH = 'high';

    // Urgent priority
    case URGENT = 'urgent';

    // Check if priority is low
    public function isLow(): bool
    {
        return $this === self::LOW;
    }

    // Check if priority is medium
    public function isMedium(): bool
    {
        return $this === self::MEDIUM;
    }

    // Check if priority is high
    public function isHigh(): bool
    {
        return $this === self::HIGH;
    }

    // Check if priority is urgent
    public function isUrgent(): bool
    {
        return $this === self::URGENT;
    }
}
