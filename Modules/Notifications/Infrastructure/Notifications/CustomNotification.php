<?php

namespace Modules\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;

/**
 * Class CustomNotification
 *
 * A custom Laravel notification that supports multiple channels
 * (database, email, SMS, broadcast) and handles priorities, types,
 * and localized messages.
 *
 * Architecture Notes:
 * - This class lives in the Infrastructure layer
 * - Encapsulates presentation logic (email, broadcast, DB payloads)
 * - Domain layer provides NotificationType, Priority, Channels
 * - Fully queueable with ShouldQueue
 *
 * @package Modules\Notifications\Infrastructure\Notifications
 */
class CustomNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param NotificationType $type Domain notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param NotificationChannel[] $channels Delivery channels
     * @param string|null $actionUrl Optional action URL
     * @param array|null $metadata Optional metadata
     * @param NotificationPriority $priority Priority for badge/ordering
     * @param string $locale Locale for localization
     */
    public function __construct(
        private NotificationType $type,
        private string $title,
        private string $message,
        private array $channels = [NotificationChannel::DATABASE],
        private ?string $actionUrl = null,
        private ?array $metadata = null,
        private NotificationPriority $priority = NotificationPriority::MEDIUM,
        private string $locale = 'fa'
    ) {
        $this->onQueue('notifications');
        $this->afterCommit();
    }

    /**
     * Determine which channels the notification should be sent through.
     *
     * @param object $notifiable
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return array_map(fn(NotificationChannel $c) => match ($c) {
            NotificationChannel::DATABASE => 'database',
            NotificationChannel::EMAIL => 'mail',
            NotificationChannel::SMS => 'vonage',
            NotificationChannel::PUSH => 'broadcast',
        }, $this->channels);
    }

    /**
     * Transform notification for email delivery.
     *
     * Uses MailMessage and respects the locale.
     *
     * @param object $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting($this->getGreeting())
            ->line($this->message)
            ->locale($this->locale);

        if ($this->actionUrl) {
            $mail->action(
                $this->metadata['action_label'] ?? trans('notifications.default_action_label'),
                $this->actionUrl
            );
        }

        if ($this->type === NotificationType::ERROR) {
            return $mail->error();
        }

        return $mail;
    }

    /**
     * Transform notification for database storage.
     *
     * @param object $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'priority' => $this->priority->value,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'metadata' => $this->metadata ?? [],
            'badge_color' => $this->priority->badgeColor(),
            'sent_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Transform notification for broadcasting (push).
     *
     * @param object $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id ?? uniqid(),
            'type' => $this->type->value,
            'priority' => $this->priority->value,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'metadata' => $this->metadata ?? [],
            'badge_color' => $this->priority->badgeColor(),
            'read_at' => null,
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get a localized greeting based on NotificationType.
     *
     * @return string
     */
    private function getGreeting(): string
    {
        return match ($this->type) {
            NotificationType::SUCCESS => trans('notifications.greetings.success'),
            NotificationType::WARNING => trans('notifications.greetings.warning'),
            NotificationType::ERROR => trans('notifications.greetings.error'),
            default => trans('notifications.greetings.default'),
        };
    }
}
