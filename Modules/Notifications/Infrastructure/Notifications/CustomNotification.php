<?php

namespace Modules\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
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
 */
class CustomNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Notification title
     */
    private string $title;

    /**
     * Notification message
     */
    private string $message;

    /**
     * Delivery channels
     *
     * @var NotificationChannel[]
     */
    private array $channels;

    /**
     * Optional action URL
     */
    private ?string $actionUrl;

    /**
     * Optional metadata payload
     *
     * @var array<string, mixed>|null
     */
    private ?array $metadata;

    /**
     * Notification type
     */
    private NotificationType $type;

    /**
     * Notification priority
     */
    private NotificationPriority $priority;

    /**
     * Locale for localization (use parent property)
     */
    private string $notificationLocale;

    /**
     * Create a new notification instance.
     *
     * @param  NotificationType  $type  Domain notification type
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  NotificationChannel[]  $channels  Delivery channels
     * @param  string|null  $actionUrl  Optional action URL
     * @param  array<string, mixed>|null  $metadata  Optional metadata
     * @param  NotificationPriority  $priority  Priority for badge/ordering
     * @param  string  $locale  Locale for localization
     */
    public function __construct(
        NotificationType $type,
        string $title,
        string $message,
        array $channels = [NotificationChannel::DATABASE],
        ?string $actionUrl = null,
        ?array $metadata = null,
        NotificationPriority $priority = NotificationPriority::MEDIUM,
        string $locale = 'en'
    ) {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->channels = $channels;
        $this->actionUrl = $actionUrl;
        $this->metadata = $metadata;
        $this->priority = $priority;
        $this->notificationLocale = $locale;

        $this->onQueue('notifications');
        $this->afterCommit();
    }

    /**
     * Get notification title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get notification message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get notification type.
     */
    public function getType(): NotificationType
    {
        return $this->type;
    }

    /**
     * Get notification priority.
     */
    public function getPriority(): NotificationPriority
    {
        return $this->priority;
    }

    /**
     * Get delivery channels.
     *
     * @return NotificationChannel[]
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Get action URL.
     */
    public function getActionUrl(): ?string
    {
        return $this->actionUrl;
    }

    /**
     * Get metadata.
     *
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Determine which channels the notification should be sent through.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return array_map(fn (NotificationChannel $c) => match ($c) {
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
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting($this->getGreeting())
            ->line($this->message);

        // FIX: Set locale on mail message using parent property
        $this->locale = $this->notificationLocale;

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
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => uniqid(),
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
