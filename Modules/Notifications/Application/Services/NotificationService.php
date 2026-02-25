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
 *
 * Responsibilities:
 * - Send notifications to users via multiple channels
 * - Retrieve and filter notifications
 * - Track read/unread state
 * - Soft-delete notifications
 *
 * Application service layer only; no domain logic should be introduced here.
 */
class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $repository
    ) {}

    /**
     * Send notification to a user via specified channels.
     *
     * @param SendNotificationDTO $dto
     * @return NotificationEntity Persisted notification entity
     *
     * @throws \InvalidArgumentException if recipient not found
     */
    public function sendNotification(SendNotificationDTO $dto): NotificationEntity
    {
        Log::channel('domain')->info('Sending notification', [
            'recipient_id' => $dto->recipientId,
            'type' => $dto->type->value,
            'priority' => $dto->priority->value,
            'channels' => array_map(fn($c) => $c->value, $dto->channels),
        ]);

        // Verify recipient exists
        $user = UserModel::find($dto->recipientId);
        if (! $user) {
            throw new \InvalidArgumentException(
                __("notification.errors.user_not_found", ['id' => $dto->recipientId])
            );
        }

        // Create content value object
        $content = new NotificationContent(
            title: $dto->title,
            body: $dto->message,
            actionLabel: $dto->actionLabel,
            actionUrl: $dto->actionUrl
        );

        // Create immutable notification entity
        $entity = new NotificationEntity(
            id: uniqid('notif_', true),
            recipientId: $dto->recipientId,
            type: $dto->type,
            priority: $dto->priority,
            category: $dto->category,
            content: $content,
            metadata: $dto->metadata,
            createdAt: now()
        );

        // Persist entity
        $savedEntity = $this->repository->create($entity);

        // Dispatch notifications to channels asynchronously
        foreach ($dto->channels as $channel) {
            DispatchNotificationJob::dispatch(
                notificationId: $savedEntity->getId(),
                channel: $channel,
                locale: $user->locale ?? 'fa'
            )->onQueue('notifications');
        }

        Log::channel('domain')->info('Notification queued for delivery', [
            'notification_id' => $savedEntity->getId(),
        ]);

        return $savedEntity;
    }

    /**
     * Retrieve notifications for a user with optional filters.
     *
     * @param int $userId
     * @param NotificationFilterDTO|null $filters
     * @param int $page Pagination page (not applied here, left for infrastructure)
     * @param int $perPage Pagination size (not applied here, left for infrastructure)
     * @return NotificationEntity[]
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
