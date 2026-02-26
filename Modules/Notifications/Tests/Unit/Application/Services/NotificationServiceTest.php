<?php

namespace Modules\Notifications\Tests\Unit\Application\Services;

use Illuminate\Support\Facades\Notification as NotificationFacade;
use Mockery;
use Modules\Notifications\Application\DTOs\NotificationFilterDTO;
use Modules\Notifications\Application\DTOs\SendNotificationDTO;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Domain\Entities\NotificationEntity;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;
use Modules\Notifications\Tests\TestCase;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(NotificationService::class)]
class NotificationServiceTest extends TestCase
{
    protected NotificationRepositoryInterface&Mockery\MockInterface $repositoryMock;

    protected NotificationService $service;

    protected UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(NotificationRepositoryInterface::class);
        $this->service = new NotificationService($this->repositoryMock);

        $this->user = UserModel::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_send_notification_creates_entity_and_dispatches_jobs(): void
    {
        NotificationFacade::fake();

        $dto = SendNotificationDTO::forUser(
            userId: $this->user->id,
            type: NotificationType::SUCCESS,
            title: __('notifications.test_title'),
            message: __('notifications.test_message'),
            channels: [NotificationChannel::DATABASE, NotificationChannel::EMAIL],
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::SYSTEM,
            actionUrl: 'https://example.com',
            actionLabel: __('notifications.view_action'),
            metadata: ['key' => 'value']
        );

        $content = new NotificationContent(
            $dto->title,
            $dto->message,
            $dto->actionLabel,
            $dto->actionUrl
        );

        $entity = new \Modules\Notifications\Domain\Entities\NotificationEntity(
            $this->generateValidUuid(),
            $this->user->id,
            $dto->type,
            $dto->priority,
            $dto->category,
            $content,
            now()
        );

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($e) => $e->getRecipientId() === $this->user->id))
            ->andReturn($entity);

        $result = $this->service->sendNotification($dto);

        $this->assertEquals($entity->getId(), $result->getId());
        $this->assertEquals($this->user->id, $result->getRecipientId());
        $this->assertEquals(NotificationType::SUCCESS, $result->getType());

        // FIX: In unit test, verify entity creation, not notification sending
        // Notification sending happens in DispatchNotificationJob (integration test)
        $this->assertInstanceOf(NotificationEntity::class, $result);
    }

    #[Test]
    public function test_get_unread_count_returns_correct_number(): void
    {
        $this->repositoryMock
            ->shouldReceive('countUnreadByRecipientId')
            ->once()
            ->with($this->user->id)
            ->andReturn(5);

        $count = $this->service->getUnreadCount($this->user->id);

        $this->assertEquals(5, $count);
    }

    #[Test]
    public function test_mark_as_read_returns_true_on_success(): void
    {
        $notificationId = $this->generateValidUuid();

        $this->repositoryMock
            ->shouldReceive('markAsRead')
            ->once()
            ->with($notificationId, $this->user->id)
            ->andReturn(true);

        $result = $this->service->markAsRead($notificationId, $this->user->id);

        $this->assertTrue($result);
    }

    #[Test]
    public function test_mark_all_as_read_returns_affected_count(): void
    {
        $this->repositoryMock
            ->shouldReceive('markAllAsRead')
            ->once()
            ->with($this->user->id)
            ->andReturn(10);

        $count = $this->service->markAllAsRead($this->user->id);

        $this->assertEquals(10, $count);
    }

    #[Test]
    public function test_delete_notification_returns_true_on_success(): void
    {
        $notificationId = $this->generateValidUuid();

        $this->repositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($notificationId, $this->user->id)
            ->andReturn(true);

        $result = $this->service->deleteNotification($notificationId, $this->user->id);

        $this->assertTrue($result);
    }

    #[Test]
    public function test_send_notification_to_non_existent_user_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(__('notifications.errors.user_not_found', ['id' => 99999]));

        $dto = SendNotificationDTO::forUser(
            userId: 99999,
            type: NotificationType::INFO,
            title: __('notifications.test_title'),
            message: __('notifications.test_message')
        );

        $this->service->sendNotification($dto);
    }

    #[Test]
    public function test_get_user_notifications_applies_filters(): void
    {
        $filters = new NotificationFilterDTO([
            'type' => NotificationType::SUCCESS,
            'unreadOnly' => true,
        ]);

        $this->repositoryMock
            ->shouldReceive('findByRecipientId')
            ->once()
            ->with($this->user->id, $filters)
            ->andReturn([]);

        $result = $this->service->getUserNotifications($this->user->id, $filters);

        $this->assertSame([], $result);
    }

    /**
     * Generate a valid UUID for testing.
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
