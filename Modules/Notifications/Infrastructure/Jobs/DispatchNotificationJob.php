<?php

namespace Modules\Notifications\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Infrastructure\Notifications\CustomNotification;
use Modules\Notifications\Infrastructure\Persistence\Models\NotificationModel;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

/**
 * Class DispatchNotificationJob
 *
 * Queued job responsible for sending notifications to users through
 * multiple channels (database, email, push, etc.).
 *
 * Handles missing notifications or users gracefully and logs failures.
 */
class DispatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Maximum runtime in seconds */
    public $timeout = 60;

    /** @var int Number of attempts before failing */
    public $tries = 3;

    /** @var array<int> Backoff intervals in seconds */
    public $backoff = [10, 30, 60];

    /** @var bool Automatically delete job if model is missing */
    public $deleteWhenMissingModels = true;

    /**
     * @param  string  $notificationId  UUID of the notification
     * @param  string  $channel  Notification channel (database, email, push, etc.)
     * @param  string  $locale  Language/locale code for translations
     */
    public function __construct(
        public string $notificationId,
        public string $channel,
        public string $locale = 'en'
    ) {}

    /**
     * Execute the queued job.
     */
    public function handle(): void
    {
        $notification = NotificationModel::find($this->notificationId);

        if (! $notification) {
            Log::warning("Notification {$this->notificationId} not found for dispatch");

            return;
        }

        $user = UserModel::find($notification->notifiable_id);

        if (! $user) {
            Log::warning("User {$notification->notifiable_id} not found for notification");

            return;
        }

        $data = $notification->data ?? [];

        // FIX: Convert string values from database to domain enums
        $customNotification = new CustomNotification(
            NotificationType::from($notification->type),
            $data['title'] ?? 'Notification',
            $data['message'] ?? '',
            [NotificationChannel::from($this->channel)],
            $data['action_url'] ?? null,
            $data['metadata'] ?? null,
            NotificationPriority::from($notification->priority),
            $this->locale
        );

        $user->notify($customNotification);

        Log::info("Notification dispatched via {$this->channel}", [
            'notification_id' => $this->notificationId,
            'user_id' => $user->id,
            'channel' => $this->channel,
        ]);
    }

    /**
     * Handle a failed job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification dispatch failed', [
            'notification_id' => $this->notificationId,
            'channel' => $this->channel,
            'error' => $exception->getMessage(),
            'trace' => config('app.debug') ? $exception->getTraceAsString() : null,
        ]);
    }
}
