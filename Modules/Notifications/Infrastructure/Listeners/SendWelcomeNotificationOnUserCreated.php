<?php

namespace Modules\Notifications\Infrastructure\Listeners;

use Modules\Notifications\Application\DTOs\SendNotificationDTO;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Users\Domain\Events\UserCreated;

/**
 * Listener that reacts to the custom UserCreated event fired by the Users module
 * during registration. This keeps perfect decoupling between modules.
 */
final class SendWelcomeNotificationOnUserCreated
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(UserCreated $event): void
    {
        $user = $event->user; // UserModel instance

        $dto = SendNotificationDTO::forUser(
            userId: $user->id,                                                  // 1. int
            type: NotificationType::SUCCESS,                                    // 2. NotificationType
            title: __('notifications.welcome.title', ['name' => $user->name]),  // 3. string
            message: __('notifications.welcome.body', ['name' => $user->name]), // 4. string
            channels: [NotificationChannel::DATABASE],                          // 5. array
            priority: NotificationPriority::MEDIUM,                             // 6. NotificationPriority
            category: NotificationCategory::SYSTEM,                             // 7. NotificationCategory
            actionUrl: null,                                                    // 8. ?string ← FIX: Was metadata array
            actionLabel: null,                                                  // 9. ?string ← FIX: Add missing parameter
            metadata: [                                                         // 10. ?array ← FIX: Move metadata here
                'event' => 'user_registered',
                'source' => 'auth.registration',
            ]
        );
        $this->notificationService->sendNotification($dto);
    }
}
