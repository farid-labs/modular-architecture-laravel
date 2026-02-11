<?php

namespace Modules\Notifications\Application\Services;

use Illuminate\Support\Facades\Notification;
use Modules\Notifications\Application\DTOs\NotificationDTO;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Infrastructure\Notifications\CustomNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Modules\Users\Infrastructure\Persistence\Models\User;

class NotificationService
{
    /**
     * Send a notification to a user
     *
     * @param array<NotificationChannel> $channels
     */
    public function sendNotification(
        int $userId,
        NotificationDTO $notificationDTO,
        array $channels = [NotificationChannel::DATABASE]
    ): void {
        /** @var User $user */
        $user = User::findOrFail($userId);

        Log::channel('domain')->info('Sending notification', [
            'user_id' => $userId,
            'type' => $notificationDTO->type->value,
            'title' => $notificationDTO->title,
            'channels' => array_map(fn($c) => $c->value, $channels)
        ]);

        $notification = new CustomNotification(
            $notificationDTO->type,
            $notificationDTO->title,
            $notificationDTO->message,
            $notificationDTO->data,
            $notificationDTO->action_url,
            $channels
        );

        Notification::sendNow($user, $notification);

        Log::channel('domain')->info('Notification sent successfully', [
            'user_id' => $userId,
            'type' => $notificationDTO->type->value,
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = DatabaseNotification::query()
            ->where('id', $notificationId)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->first();

        if (!$notification instanceof DatabaseNotification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Get all unread notifications for a user
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUnreadNotifications(int $userId): array
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(DatabaseNotification $n) => $n->toArray())
            ->all();
    }

    /**
     * Get all notifications for a user
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllNotifications(int $userId): array
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(DatabaseNotification $n) => $n->toArray())
            ->all();
    }

    /**
     * Delete a notification
     */
    public function deleteNotification(int $notificationId, int $userId): bool
    {
        $notification = DatabaseNotification::query()
            ->where('id', $notificationId)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->first();

        if (!$notification instanceof DatabaseNotification) {
            return false;
        }

        return $notification->delete() === true;
    }
}
