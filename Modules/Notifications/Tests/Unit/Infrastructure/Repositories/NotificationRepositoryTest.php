<?php

namespace Modules\Notifications\Tests\Unit\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notifications\Application\DTOs\NotificationFilterDTO;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;
use Modules\Notifications\Infrastructure\Persistence\Models\NotificationModel;
use Modules\Notifications\Infrastructure\Repositories\NotificationRepository;
use Modules\Notifications\Tests\TestCase;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(NotificationRepository::class)]
class NotificationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationRepository $repository;

    protected UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new NotificationRepository;
        $this->user = UserModel::factory()->create();
    }

    #[Test]
    public function test_create_notification_persists_to_database(): void
    {
        $content = new NotificationContent(
            __('notifications.test_title'),
            __('notifications.test_message'),
            __('notifications.view_action'),
            'https://example.com'
        );

        // FIX: Use proper UUID format for PostgreSQL
        $entity = new \Modules\Notifications\Domain\Entities\NotificationEntity(
            $this->generateValidUuid(),
            $this->user->id,
            NotificationType::INFO,
            NotificationPriority::MEDIUM,
            NotificationCategory::SYSTEM,
            $content,
            createdAt: now()
        );

        $result = $this->repository->create($entity);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'type' => 'info',
        ]);

        $this->assertEquals($entity->getId(), $result->getId());
        $this->assertEquals($this->user->id, $result->getRecipientId());
    }

    #[Test]
    public function test_find_by_id_returns_entity(): void
    {
        // FIX: Use proper UUID format
        $uuid = $this->generateValidUuid();

        NotificationModel::create([
            'id' => $uuid,
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

        $result = $this->repository->findById($uuid);

        $this->assertNotNull($result);
        $this->assertEquals($uuid, $result->getId());
        $this->assertEquals(NotificationType::SUCCESS, $result->getType());
    }

    #[Test]
    public function test_find_by_id_returns_null_for_non_existent(): void
    {
        $result = $this->repository->findById($this->generateValidUuid());

        $this->assertNull($result);
    }

    #[Test]
    public function test_mark_as_read_updates_read_at(): void
    {
        $uuid = $this->generateValidUuid();

        NotificationModel::create([
            'id' => $uuid,
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        $result = $this->repository->markAsRead($uuid, $this->user->id);

        $this->assertTrue($result);

        $notification = NotificationModel::find($uuid);
        $this->assertNotNull($notification);
        $this->assertNotNull($notification->read_at);
    }

    #[Test]
    public function test_mark_all_as_read_updates_all_unread(): void
    {
        $uuid1 = $this->generateValidUuid();
        $uuid2 = $this->generateValidUuid();

        NotificationModel::create([
            'id' => $uuid1,
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        NotificationModel::create([
            'id' => $uuid2,
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

        $notification1 = NotificationModel::find($uuid1);
        $this->assertNotNull($notification1);
        $this->assertNotNull($notification1->read_at);

        $notification2 = NotificationModel::find($uuid2);
        $this->assertNotNull($notification2);
        $this->assertNotNull($notification2->read_at);
    }

    #[Test]
    public function test_count_unread_returns_correct_number(): void
    {
        NotificationModel::create([
            'id' => $this->generateValidUuid(),
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        NotificationModel::create([
            'id' => $this->generateValidUuid(),
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        NotificationModel::create([
            'id' => $this->generateValidUuid(),
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

    #[Test]
    public function test_delete_notification_sets_deleted_at(): void
    {
        $uuid = $this->generateValidUuid();

        NotificationModel::create([
            'id' => $uuid,
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        $result = $this->repository->delete($uuid, $this->user->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('notifications', ['id' => $uuid]);
    }

    #[Test]
    public function test_find_by_recipient_id_applies_filters(): void
    {
        $uuid1 = $this->generateValidUuid();
        $uuid2 = $this->generateValidUuid();

        NotificationModel::create([
            'id' => $uuid1,
            'type' => 'success',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
            'read_at' => null,
        ]);

        NotificationModel::create([
            'id' => $uuid2,
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
        $this->assertEquals($uuid1, $result[0]->getId());
    }

    /**
     * Generate a valid UUID for PostgreSQL.
     */
    private function generateValidUuid(): string
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
}
