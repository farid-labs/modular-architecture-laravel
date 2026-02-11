<?php

namespace Modules\Notifications\Domain\Enums;

enum NotificationChannel: string
{
    case DATABASE = 'database';
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';
}
