<?php

namespace Modules\Notifications\Tests\Unit\Infrastructure\Repositories;

use Modules\Notifications\Application\DTOs\NotificationFilterDTO;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;
use Modules\Notifications\Infrastructure\Persistence\Models\NotificationModel;
use Modules\Notifications\Infrastructure\Repositories\NotificationRepository;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Notifications\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for NotificationRepository.
 *
 * Ensures correct persistence, retrieval, and management of notification entities.
 *
 * @covers \Modules\Notifications\Infrastructure\Repositories\NotificationRepository
 */
class NotificationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationRepository $repository;
    protected UserModel $user;

    /**
     * Set up test repository and user.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new NotificationRepository();
        $this->user = UserModel::factory()->create();
    }

    /**
     * Test that creating a notification persists the entity to the database.
     */
    public function test_create_notification_persists_to_database(): void
    {
        $content = new NotificationContent(
            title: __('notifications.test_title'),
            body: __('notifications.test_message'),
            actionLabel: __('notifications.view_action'),
            actionUrl: 'https://example.com'
        );

        $entity = new \Modules\Notifications\Domain\Entities\NotificationEntity(
            id: 'notif_test_create',
            recipientId: $this->user->id,
            type: NotificationType::INFO,
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::SYSTEM,
            content: $content,
            createdAt: now()
        );

        $result = $this->repository->create($entity);

        $this->assertDatabaseHas('notifications', [
            'id' => 'notif_test_create',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'type' => 'info',
        ]);

        $this->assertEquals('notif_test_create', $result->getId());
        $this->assertEquals($this->user->id, $result->getRecipientId());
    }

    /**
     * Test finding a notification by ID returns the correct entity.
     */
    public function test_find_by_id_returns_entity(): void
    {
        NotificationModel::create([
            'id' => 'notif_test_find',
            'type' => 'success',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => [
                'title' => __('notifications.test_title'),
                'message' => __('notifications.test_message'),
            ],
            'read_at' => null,
        ]);

        $result = $this->repository->findById('notif_test_find');

        $this->assertNotNull($result);
        $this->assertEquals('notif_test_find', $result->getId());
        $this->assertEquals(NotificationType::SUCCESS, $result->getType());
    }

    /**
     * Test finding a non-existent notification ID returns null.
     */
    public function test_find_by_id_returns_null_for_non_existent(): void
    {
        $result = $this->repository->findById('non_existent_id');

        $this->assertNull($result);
    }

    /**
     * Test marking a notification as read updates the read_at timestamp.
     */
    public function test_mark_as_read_updates_read_at(): void
    {
        NotificationModel::create([
            'id' => 'notif_test_read',
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        $result = $this->repository->markAsRead('notif_test_read', $this->user->id);

        $this->assertTrue($result);
        $this->assertNotNull(NotificationModel::find('notif_test_read')->read_at);
    }

    /**
     * Test marking all notifications as read updates all unread notifications.
     */
    public function test_mark_all_as_read_updates_all_unread(): void
    {
        NotificationModel::create([
            'id' => 'notif_1',
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        NotificationModel::create([
            'id' => 'notif_2',
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        $count = $this->repository->markAllAsRead($this->user->id);

        $this->assertEquals(2, $count);
        $this->assertNotNull(NotificationModel::find('notif_1')->read_at);
        $this->assertNotNull(NotificationModel::find('notif_2')->read_at);
    }

    /**
     * Test counting unread notifications returns correct number.
     */
    public function test_count_unread_returns_correct_number(): void
    {
        NotificationModel::create([
            'id' => 'notif_unread_1',
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        NotificationModel::create([
            'id' => 'notif_unread_2',
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        NotificationModel::create([
            'id' => 'notif_read',
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => now(),
        ]);

        $count = $this->repository->countUnreadByRecipientId($this->user->id);

        $this->assertEquals(2, $count);
    }

    /**
     * Test deleting a notification sets the deleted_at timestamp (soft delete).
     */
    public function test_delete_notification_sets_deleted_at(): void
    {
        NotificationModel::create([
            'id' => 'notif_test_delete',
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        $result = $this->repository->delete('notif_test_delete', $this->user->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('notifications', ['id' => 'notif_test_delete']);
    }

    /**
     * Test retrieving notifications by recipient ID with applied filters.
     */
    public function test_find_by_recipient_id_applies_filters(): void
    {
        NotificationModel::create([
            'id' => 'notif_type_1',
            'type' => 'success',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        NotificationModel::create([
            'id' => 'notif_type_2',
            'type' => 'error',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        $filters = new NotificationFilterDTO([
            'type' => NotificationType::SUCCESS,
        ]);

        $result = $this->repository->findByRecipientId($this->user->id, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('notif_type_1', $result[0]->getId());
    }
}
