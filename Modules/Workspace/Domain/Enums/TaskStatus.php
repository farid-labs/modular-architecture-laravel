<?php

namespace Modules\Workspace\Domain\Enums;

/**
 * Enum representing the status of a task.
 * Provides helper methods to check specific task states.
 */
enum TaskStatus: string
{
    // Task is pending and not yet started
    case PENDING = 'pending';

    // Task is currently in progress
    case IN_PROGRESS = 'in_progress';

    // Task has been completed
    case COMPLETED = 'completed';

    // Task is blocked due to an issue
    case BLOCKED = 'blocked';

    // Task has been cancelled
    case CANCELLED = 'cancelled';

    // Check if task is pending
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    // Check if task is in progress
    public function isInProgress(): bool
    {
        return $this === self::IN_PROGRESS;
    }

    // Check if task is completed
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    // Check if task is blocked
    public function isBlocked(): bool
    {
        return $this === self::BLOCKED;
    }

    // Check if task is cancelled
    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }
}
