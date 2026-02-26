<?php

namespace Modules\Notifications\Infrastructure\Repositories;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\Application\DTOs\NotificationFilterDTO;
use Modules\Notifications\Domain\Entities\NotificationEntity;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;
use Modules\Notifications\Infrastructure\Persistence\Models\NotificationModel;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

/**
 * Repository: NotificationRepository
 *
 * Eloquent implementation of NotificationRepositoryInterface.
 * Handles all database operations for notifications.
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    public function findById(string $id): ?NotificationEntity
    {
        $model = NotificationModel::find($id);

        return $model ? $this->mapToEntity($model) : null;
    }

    public function findByRecipientId(
        int $recipientId,
        ?NotificationFilterDTO $filters = null,
        int $page = 1,
        int $perPage = 15
    ): array {
        $query = NotificationModel::query()
            ->where('notifiable_type', UserModel::class)
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

        return $models->map(fn ($m) => $this->mapToEntity($m))->toArray();
    }

    public function findUnreadByRecipientId(int $recipientId, ?int $limit = null): array
    {
        $query = NotificationModel::query()
            ->where('notifiable_type', UserModel::class)
            ->where('notifiable_id', $recipientId)
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(fn ($m) => $this->mapToEntity($m))->toArray();
    }

    public function countUnreadByRecipientId(int $recipientId): int
    {
        return NotificationModel::query()
            ->where('notifiable_type', UserModel::class)
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
            'notifiable_type' => UserModel::class, // FIX: Use UserModel::class instead of hardcoded string
            'notifiable_id' => $entity->getRecipientId(),
            'data' => $entity->getContent()->toArray(),
            'read_at' => $entity->getReadAt()?->toDateTimeString(),
            'deleted_at' => $entity->getDeletedAt()?->toDateTimeString(),
            'locale' => 'en',
            'channels' => ['database'],
            'created_at' => $entity->getCreatedAt()?->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        $model = NotificationModel::create($data);

        return $this->mapToEntity($model);
    }

    public function markAsRead(string $id, int $recipientId): bool
    {

        // Find notification first for logging
        $notification = NotificationModel::find($id);

        if (! $notification) {
            Log::channel('domain')->warning('Notification not found', [
                'id' => $id,
                'recipient_id' => $recipientId,
            ]);

            return false;
        }

        Log::channel('domain')->info('Notification found', [
            'id' => $id,
            'notifiable_id' => $notification->notifiable_id,
            'notifiable_type' => $notification->notifiable_type,
            'expected_recipient' => $recipientId,
            'matches' => $notification->notifiable_id === $recipientId,
        ]);

        $affected = NotificationModel::query()
            ->where('id', $id)
            ->where('notifiable_type', UserModel::class)
            ->where('notifiable_id', $recipientId)  // ← Security check
            ->whereNull('deleted_at')
            ->update(['read_at' => now()]);

        Log::channel('domain')->info('Update result', [
            'affected' => $affected,
        ]);

        return $affected > 0;
    }

    public function markAllAsRead(int $recipientId): int
    {
        return NotificationModel::query()
            ->where('notifiable_type', UserModel::class)
            ->where('notifiable_id', $recipientId)
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->update(['read_at' => now()]);
    }

    public function delete(string $id, int $recipientId): bool
    {
        $affected = NotificationModel::query()
            ->where('id', $id)
            ->where('notifiable_type', UserModel::class)
            ->where('notifiable_id', $recipientId)
            ->update(['deleted_at' => now()]);

        return $affected > 0;
    }

    public function deleteAllForRecipient(int $recipientId): int
    {
        return NotificationModel::query()
            ->where('notifiable_type', UserModel::class)
            ->where('notifiable_id', $recipientId)
            ->update(['deleted_at' => now()]);
    }

    private function mapToEntity(NotificationModel $model): NotificationEntity
    {
        $data = $model->data ?? [];

        return new NotificationEntity(
            $model->id,
            $model->notifiable_id,
            NotificationType::from($model->type ?? 'info'),
            NotificationPriority::from($model->priority ?? 'medium'),
            NotificationCategory::from($model->category ?? 'system'),
            new NotificationContent(
                $data['title'] ?? 'Notification',
                $data['message'] ?? '',
                $data['action_label'] ?? null,
                $data['action_url'] ?? null
            ),
            $model->read_at,
            $model->deleted_at,
            $model->created_at,
            $data['metadata'] ?? null
        );
    }
}
