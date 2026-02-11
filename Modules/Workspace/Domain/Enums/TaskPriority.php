<?php

namespace Modules\Workspace\Domain\Enums;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function isLow(): bool
    {
        return $this === self::LOW;
    }

    public function isMedium(): bool
    {
        return $this === self::MEDIUM;
    }

    public function isHigh(): bool
    {
        return $this === self::HIGH;
    }

    public function isUrgent(): bool
    {
        return $this === self::URGENT;
    }
}
