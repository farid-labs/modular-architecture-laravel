<?php

namespace Modules\Notifications\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Domain\Enums\NotificationChannel;
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

    /** @var array Backoff intervals in seconds */
    public $backoff = [10, 30, 60];

    /** @var bool Automatically delete job if model is missing */
    public $deleteWhenMissingModels = true;

    /**
     * @param string $notificationId UUID of the notification
     * @param string $channel Notification channel (database, email, push, etc.)
     * @param string $locale Language/locale code for translations
     */
    public function __construct(
        private string $notificationId,
        private string $channel,
        private string $locale = 'fa'
    ) {}

    /**
     * Execute the queued job.
     *
     * @return void
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

        $customNotification = new CustomNotification(
            type: $notification->type,
            title: $data['title'] ?? 'Notification',
            message: $data['message'] ?? '',
            channels: [NotificationChannel::from($this->channel)],
            actionUrl: $data['action_url'] ?? null,
            metadata: $data['metadata'] ?? null,
            priority: $notification->priority,
            locale: $this->locale
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
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Notification dispatch failed", [
            'notification_id' => $this->notificationId,
            'channel' => $this->channel,
            'error' => $exception->getMessage(),
            'trace' => config('app.debug') ? $exception->getTraceAsString() : null,
        ]);
    }
}
