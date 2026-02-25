<?php

namespace Modules\Notifications\Infrastructure\Repositories;

use Modules\Notifications\Application\DTOs\NotificationFilterDTO;
use Modules\Notifications\Domain\Entities\NotificationEntity;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;
use Modules\Notifications\Infrastructure\Persistence\Models\NotificationModel;
use Illuminate\Support\Collection;

/**
 * Repository: NotificationRepository
 *
 * Eloquent implementation of NotificationRepositoryInterface.
 * Handles all database operations for notifications.
 *
 * Responsibilities:
 * - Map database models to domain entities
 * - Apply filters, pagination, and soft deletes
 * - Maintain domain purity by returning NotificationEntity
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    public function findById(string $id): ?NotificationEntity
    {
        $model = NotificationModel::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * Find notifications for a recipient with optional filters.
     *
     * @param int $recipientId
     * @param NotificationFilterDTO|null $filters
     * @param int $page
     * @param int $perPage
     * @return NotificationEntity[]
     */
    public function findByRecipientId(
        int $recipientId,
        ?NotificationFilterDTO $filters = null,
        int $page = 1,
        int $perPage = 15
    ): array {
        $query = NotificationModel::query()
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $recipientId)
            ->whereNull('deleted_at');

        if ($filters) {
            if ($filters->type) {
                $query->where('type', $filters->type->value);
            }
            if ($filters->category) {
                $query->where('category', $filters->category->value);
            }
            if ($filters->unreadOnly === true) {
                $query->whereNull('read_at');
            }
            if ($filters->startDate) {
                $query->where('created_at', '>=', $filters->startDate);
            }
            if ($filters->endDate) {
                $query->where('created_at', '<=', $filters->endDate);
            }
        }

        $models = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return $models->map(fn($m) => $this->mapToEntity($m))->toArray();
    }

    public function findUnreadByRecipientId(int $recipientId, ?int $limit = null): array
    {
        $query = NotificationModel::query()
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $recipientId)
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(fn($m) => $this->mapToEntity($m))->toArray();
    }

    public function countUnreadByRecipientId(int $recipientId): int
    {
        return NotificationModel::query()
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $recipientId)
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->count();
    }

    public function create(NotificationEntity $entity): NotificationEntity
    {
        $data = [
            'id' => $entity->getId(),
            'type' => $entity->getType()->value,
            'priority' => $entity->getPriority()->value,
            'category' => $entity->getCategory()->value,
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $entity->getRecipientId(),
            'data' => $entity->getContent()->toArray(),
            'read_at' => $entity->getReadAt()?->toDateTimeString(),
            'deleted_at' => $entity->getDeletedAt()?->toDateTimeString(),
            'locale' => 'fa',
            'channels' => ['database'],
            'created_at' => $entity->getCreatedAt()?->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        $model = NotificationModel::create($data);

        return $this->mapToEntity($model);
    }

    public function markAsRead(string $id, int $recipientId): bool
    {
        $affected = NotificationModel::query()
            ->where('id', $id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $recipientId)
            ->whereNull('deleted_at')
            ->update(['read_at' => now()]);

        return $affected > 0;
    }

    public function markAllAsRead(int $recipientId): int
    {
        return NotificationModel::query()
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $recipientId)
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->update(['read_at' => now()]);
    }

    public function delete(string $id, int $recipientId): bool
    {
        $affected = NotificationModel::query()
            ->where('id', $id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $recipientId)
            ->update(['deleted_at' => now()]);

        return $affected > 0;
    }

    public function deleteAllForRecipient(int $recipientId): int
    {
        return NotificationModel::query()
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $recipientId)
            ->update(['deleted_at' => now()]);
    }

    /**
     * Map an Eloquent model to a domain NotificationEntity.
     *
     * @param NotificationModel $model
     * @return NotificationEntity
     */
    private function mapToEntity(NotificationModel $model): NotificationEntity
    {
        $data = $model->data ?? [];

        return new NotificationEntity(
            id: $model->id,
            recipientId: $model->notifiable_id,
            type: NotificationType::from($model->type ?? 'info'),
            priority: NotificationPriority::from($model->priority ?? 'medium'),
            category: NotificationCategory::from($model->category ?? 'system'),
            content: new NotificationContent(
                title: $data['title'] ?? 'Notification',
                body: $data['message'] ?? '',
                actionLabel: $data['action_label'] ?? null,
                actionUrl: $data['action_url'] ?? null
            ),
            readAt: $model->read_at,
            deletedAt: $model->deleted_at,
            createdAt: $model->created_at,
            metadata: $data['metadata'] ?? null
        );
    }
}
