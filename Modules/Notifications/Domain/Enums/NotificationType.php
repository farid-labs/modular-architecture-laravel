<?php

namespace Modules\Notifications\Domain\Enums;

enum NotificationType: string
{
    case INFO = 'info';
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case ERROR = 'error';
    case SYSTEM = 'system';
}
