<?php

namespace Modules\Notifications\Domain\Repositories;

use Modules\Notifications\Application\DTOs\NotificationFilterDTO;
use Modules\Notifications\Domain\Entities\NotificationEntity;

/**
 * Interface: NotificationRepositoryInterface
 *
 * Defines the contract for notification data access.
 * 
 * Responsibilities:
 * - Encapsulate persistence concerns.
 * - Provide retrieval, creation, update, and deletion operations.
 * - Keep domain layer independent of infrastructure details.
 */
interface NotificationRepositoryInterface
{
    /**
     * Retrieve a notification by its unique identifier.
     *
     * @param string $id Notification UUID
     * @return NotificationEntity|null
     */
    public function findById(string $id): ?NotificationEntity;

    /**
     * Retrieve all notifications for a specific recipient, optionally filtered.
     *
     * @param int $recipientId Recipient user ID
     * @param NotificationFilterDTO|null $filters Optional filter DTO
     * @return NotificationEntity[] Array of NotificationEntity
     */
    public function findByRecipientId(int $recipientId, ?NotificationFilterDTO $filters = null): array;

    /**
     * Retrieve unread notifications for a specific recipient.
     *
     * @param int $recipientId Recipient user ID
     * @param int|null $limit Optional maximum number of results
     * @return NotificationEntity[] Array of unread NotificationEntity
     */
    public function findUnreadByRecipientId(int $recipientId, ?int $limit = null): array;

    /**
     * Count unread notifications for a specific recipient.
     *
     * @param int $recipientId Recipient user ID
     * @return int Total unread notifications
     */
    public function countUnreadByRecipientId(int $recipientId): int;

    /**
     * Persist a new notification entity.
     *
     * @param NotificationEntity $entity Notification to persist
     * @return NotificationEntity Persisted entity (may include database-generated fields)
     */
    public function create(NotificationEntity $entity): NotificationEntity;

    /**
     * Mark a single notification as read for a recipient.
     *
     * @param string $id Notification UUID
     * @param int $recipientId Recipient user ID
     * @return bool True if the operation succeeded
     */
    public function markAsRead(string $id, int $recipientId): bool;

    /**
     * Mark all notifications as read for a recipient.
     *
     * @param int $recipientId Recipient user ID
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(int $recipientId): int;

    /**
     * Delete a specific notification for a recipient.
     *
     * @param string $id Notification UUID
     * @param int $recipientId Recipient user ID
     * @return bool True if deletion succeeded
     */
    public function delete(string $id, int $recipientId): bool;

    /**
     * Delete all notifications for a specific recipient.
     *
     * @param int $recipientId Recipient user ID
     * @return int Number of deleted notifications
     */
    public function deleteAllForRecipient(int $recipientId): int;
}
