<?php

namespace Modules\Users\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Notifications\Application\DTOs\SendNotificationDTO;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Users\Domain\Events\UserCreated;

/**
 * Listener responsible for sending a welcome notification to newly registered users.
 * 
 * This listener delegates notification delivery to the central NotificationService,
 * ensuring decoupling of email/notification logic from the Users module.
 * Implements ShouldQueue for asynchronous execution to avoid slowing down user registration.
 */
class SendWelcomeEmailListener implements ShouldQueue
{
    /**
     * Inject the central NotificationService.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the UserCreated event.
     *
     * Constructs a SendNotificationDTO with localized content and sends it
     * via the NotificationService. Uses translation strings from /lang/en/users.php.
     *
     * @param UserCreated $event
     * @return void
     */
    public function handle(UserCreated $event): void
    {
        // Build the notification DTO using English localization strings
        $dto = SendNotificationDTO::forUser(
            userId: $event->user->id,
            type: NotificationType::SUCCESS,
            title: __('users.welcome_title', ['name' => $event->user->name]), // e.g., "Welcome, John!"
            message: __('users.welcome_message'), // e.g., "Your account has been successfully created."
            channels: [
                NotificationChannel::DATABASE, // Persist notification in the database
                NotificationChannel::EMAIL     // Send notification via email
            ],
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::SYSTEM,
            actionUrl: route('dashboard'), // Navigate user to dashboard
            actionLabel: __('users.go_to_dashboard'), // e.g., "Go to Dashboard"
            metadata: [
                'event' => 'user_registered',   // Track event type
                'user_email' => $event->user->email // Include user's email for reference
            ]
        );

        // Dispatch notification via the central service.
        // The service internally handles queuing and channel delivery.
        $this->notificationService->sendNotification($dto);
    }
}
