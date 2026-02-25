<?php

namespace Modules\Notifications\Tests\Unit\Application\Services;

use Illuminate\Support\Facades\Notification as NotificationFacade;
use Mockery;
use Modules\Notifications\Application\DTOs\SendNotificationDTO;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;
use Modules\Notifications\Infrastructure\Notifications\CustomNotification;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Notifications\Tests\TestCase;

/**
 * Unit tests for NotificationService.
 *
 * Ensures proper functioning of notification-related operations including:
 * - Creating and dispatching notifications
 * - Retrieving unread notification count
 * - Marking notifications as read
 * - Deleting notifications
 *
 * @covers \Modules\Notifications\Application\Services\NotificationService
 */
class NotificationServiceTest extends TestCase
{
    protected NotificationRepositoryInterface&Mockery\MockInterface $repositoryMock;
    protected NotificationService $service;
    protected UserModel $user;

    /**
     * Set up test environment with mocked repository and test user.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the notification repository
        $this->repositoryMock = Mockery::mock(NotificationRepositoryInterface::class);
        $this->service = new NotificationService($this->repositoryMock);

        // Create a verified test user
        $this->user = UserModel::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Tear down mocks after each test.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that sending a notification creates an entity and dispatches jobs.
     */
    public function test_send_notification_creates_entity_and_dispatches_jobs(): void
    {
        NotificationFacade::fake();

        // Arrange notification DTO
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
            title: $dto->title,
            body: $dto->message,
            actionLabel: $dto->actionLabel,
            actionUrl: $dto->actionUrl
        );

        $entity = new \Modules\Notifications\Domain\Entities\NotificationEntity(
            id: 'notif_test_123',
            recipientId: $this->user->id,
            type: $dto->type,
            priority: $dto->priority,
            category: $dto->category,
            content: $content,
            createdAt: now()
        );

        // Mock repository to return entity on create
        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn($e) => $e->getRecipientId() === $this->user->id))
            ->andReturn($entity);

        // Act: send the notification
        $result = $this->service->sendNotification($dto);

        // Assert: verify entity and dispatched notification
        $this->assertEquals($entity->getId(), $result->getId());
        $this->assertEquals($this->user->id, $result->getRecipientId());
        $this->assertEquals(NotificationType::SUCCESS, $result->getType());

        NotificationFacade::assertSentTo($this->user, CustomNotification::class, function ($notification) {
            return $notification->title === __('notifications.test_title')
                && $notification->message === __('notifications.test_message');
        });
    }

    /**
     * Test that unread notification count is retrieved correctly.
     */
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

    /**
     * Test marking a single notification as read.
     */
    public function test_mark_as_read_returns_true_on_success(): void
    {
        $notificationId = 'notif_test_123';

        $this->repositoryMock
            ->shouldReceive('markAsRead')
            ->once()
            ->with($notificationId, $this->user->id)
            ->andReturn(true);

        $result = $this->service->markAsRead($notificationId, $this->user->id);

        $this->assertTrue($result);
    }

    /**
     * Test marking all notifications as read for a user.
     */
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

    /**
     * Test deleting a notification.
     */
    public function test_delete_notification_returns_true_on_success(): void
    {
        $notificationId = 'notif_test_123';

        $this->repositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($notificationId, $this->user->id)
            ->andReturn(true);

        $result = $this->service->deleteNotification($notificationId, $this->user->id);

        $this->assertTrue($result);
    }

    /**
     * Test sending notification to a non-existent user throws exception.
     */
    public function test_send_notification_to_non_existent_user_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(__('notifications.user_not_found', ['id' => 99999]));

        $dto = SendNotificationDTO::forUser(
            userId: 99999,
            type: NotificationType::INFO,
            title: __('notifications.test_title'),
            message: __('notifications.test_message')
        );

        $this->service->sendNotification($dto);
    }

    /**
     * Test fetching user notifications with applied filters.
     */
    public function test_get_user_notifications_applies_filters(): void
    {
        $filters = new \Modules\Notifications\Application\DTOs\NotificationFilterDTO([
            'type' => NotificationType::SUCCESS,
            'unreadOnly' => true,
        ]);

        $this->repositoryMock
            ->shouldReceive('findByRecipientId')
            ->once()
            ->with($this->user->id, $filters)
            ->andReturn([]);

        $result = $this->service->getUserNotifications($this->user->id, $filters);

        $this->assertIsArray($result);
    }
}
