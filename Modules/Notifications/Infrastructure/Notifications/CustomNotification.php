<?php

namespace Modules\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Domain\Enums\NotificationChannel;

class CustomNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param array<string, mixed>|null $data
     * @param array<NotificationChannel> $channels
     */
    public function __construct(
        private NotificationType $type,
        private string $title,
        private string $message,
        private ?array $data = null,
        private ?string $actionUrl = null,
        private array $channels = [NotificationChannel::DATABASE]
    ) {
        $this->queue = 'notifications';
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $result = [];
        foreach ($this->channels as $channel) {
            $result[] = match ($channel) {
                NotificationChannel::DATABASE => 'database',
                NotificationChannel::EMAIL => 'mail',
                NotificationChannel::SMS => 'vonage',
                NotificationChannel::PUSH => 'broadcast',
            };
        }
        return $result;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello!')
            ->line($this->message);

        if ($this->actionUrl !== null) {
            $mail->action('View Details', $this->actionUrl);
        }

        $mail->line('Thank you for using our application!');

        if ($this->type === NotificationType::ERROR) {
            return $mail->error();
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data ?? [],
            'action_url' => $this->actionUrl,
            'sent_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * Handle the notification after it has been sent.
     */
    public function afterSending(object $notifiable, string $channel, mixed $response): void
    {
        Log::info('Notification sent successfully', [
            'notifiable_id' => $notifiable->id ?? 'unknown',
            'notifiable_type' => get_class($notifiable),
            'channel' => $channel,
            'type' => $this->type->value,
            'title' => $this->title,
        ]);
    }

    /**
     * Determine if the notification should be sent.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        return true;
    }
}
