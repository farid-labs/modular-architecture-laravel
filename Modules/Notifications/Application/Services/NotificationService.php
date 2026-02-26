<?php

namespace Modules\Notifications\Application\Services;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\Application\DTOs\NotificationFilterDTO;
use Modules\Notifications\Application\DTOs\SendNotificationDTO;
use Modules\Notifications\Domain\Entities\NotificationEntity;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;
use Modules\Notifications\Infrastructure\Jobs\DispatchNotificationJob;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

/**
 * Service: NotificationService
 *
 * Orchestrates notification delivery, retrieval, and lifecycle operations.
 */
class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $repository
    ) {}

    /**
     * Send notification to a user via specified channels.
     */
    public function sendNotification(SendNotificationDTO $dto): NotificationEntity
    {
        Log::channel('domain')->info('Sending notification', [
            'recipient_id' => $dto->recipientId,
            'type' => $dto->type->value,
            'priority' => $dto->priority->value,
            'channels' => array_map(fn ($c) => $c->value, $dto->channels),
        ]);

        // Verify recipient exists
        $user = UserModel::find($dto->recipientId);
        if (! $user) {
            throw new \InvalidArgumentException(
                __('notifications.errors.user_not_found', ['id' => $dto->recipientId])
            );
        }

        // Create content value object
        $content = new NotificationContent(
            $dto->title,
            $dto->message,
            $dto->actionLabel,
            $dto->actionUrl
        );

        // Create immutable notification entity
        $entity = new NotificationEntity(
            $this->generateNotificationId(),
            $dto->recipientId,
            $dto->type,
            $dto->priority,
            $dto->category,
            $content,
            null,           // ← Explicitly null
            null,        // ← Explicitly null
            now(),       // ← Carbon instance
            $dto->metadata // ← Array in correct position
        );

        // Persist entity
        $savedEntity = $this->repository->create($entity);

        // Dispatch notifications to channels asynchronously
        foreach ($dto->channels as $channel) {
            DispatchNotificationJob::dispatch(
                $savedEntity->getId(),
                $channel->value,
                $user->locale ?? 'en'
            )->onQueue('notifications');
        }

        Log::channel('domain')->info('Notification queued for delivery', [
            'notification_id' => $savedEntity->getId(),
        ]);

        return $savedEntity;
    }

    /**
     * Generate a valid UUID for notification ID.
     */
    private function generateNotificationId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }

    /**
     * Retrieve notifications for a user with optional filters.
     *
     * @return array<int, NotificationEntity>
     */
    public function getUserNotifications(
        int $userId,
        ?NotificationFilterDTO $filters = null,
        int $page = 1,
        int $perPage = 15
    ): array {
        return $this->repository->findByRecipientId($userId, $filters);
    }

    /**
     * Count unread notifications for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->repository->countUnreadByRecipientId($userId);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(string $notificationId, int $userId): bool
    {
        Log::channel('domain')->info('Marking notification as read', [
            'notification_id' => $notificationId,
            'user_id' => $userId,
        ]);

        return $this->repository->markAsRead($notificationId, $userId);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        Log::channel('domain')->info('Marking all notifications as read', [
            'user_id' => $userId,
        ]);

        return $this->repository->markAllAsRead($userId);
    }

    /**
     * Soft delete a specific notification.
     */
    public function deleteNotification(string $notificationId, int $userId): bool
    {
        Log::channel('domain')->info('Deleting notification', [
            'notification_id' => $notificationId,
            'user_id' => $userId,
        ]);

        return $this->repository->delete($notificationId, $userId);
    }

    /**
     * Soft delete all notifications for a user.
     */
    public function deleteAllNotifications(int $userId): int
    {
        Log::channel('domain')->info('Deleting all notifications', [
            'user_id' => $userId,
        ]);

        return $this->repository->deleteAllForRecipient($userId);
    }
}
