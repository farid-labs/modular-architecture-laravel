<?php

namespace Modules\Notifications\Tests\Unit\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Notifications\Domain\Entities\NotificationEntity;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;
use Modules\Notifications\Tests\TestCase;

/**
 * Unit tests for NotificationEntity.
 *
 * Ensures proper instantiation, immutability, state changes, and array conversion.
 *
 * @covers \Modules\Notifications\Domain\Entities\NotificationEntity
 */
class NotificationEntityTest extends TestCase
{
    protected NotificationContent $content;
    protected CarbonImmutable $now;

    /**
     * Set up reusable content and timestamp for tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->now = CarbonImmutable::now();

        $this->content = new NotificationContent(
            title: __('notifications.test_title'),
            body: __('notifications.test_message'),
            actionLabel: __('notifications.view_action'),
            actionUrl: 'https://example.com'
        );
    }

    /**
     * Test that a notification entity can be created with all properties.
     */
    public function test_entity_can_be_created(): void
    {
        $entity = new NotificationEntity(
            id: 'notif_test_1',
            recipientId: 1,
            type: NotificationType::INFO,
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::SYSTEM,
            content: $this->content,
            createdAt: $this->now
        );

        $this->assertEquals('notif_test_1', $entity->getId());
        $this->assertEquals(1, $entity->getRecipientId());
        $this->assertEquals(NotificationType::INFO, $entity->getType());
        $this->assertEquals(NotificationPriority::MEDIUM, $entity->getPriority());
        $this->assertEquals(NotificationCategory::SYSTEM, $entity->getCategory());
        $this->assertFalse($entity->isRead());
        $this->assertTrue($entity->isActive());
    }

    /**
     * Test that an entity is marked as read when readAt is set.
     */
    public function test_entity_is_read_when_read_at_is_set(): void
    {
        $entity = new NotificationEntity(
            id: 'notif_test_2',
            recipientId: 1,
            type: NotificationType::INFO,
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::SYSTEM,
            content: $this->content,
            readAt: $this->now,
            createdAt: $this->now
        );

        $this->assertTrue($entity->isRead());
        $this->assertEquals($this->now, $entity->getReadAt());
    }

    /**
     * Test that markAsRead returns a new immutable instance.
     */
    public function test_mark_as_read_returns_new_instance(): void
    {
        $entity = new NotificationEntity(
            id: 'notif_test_3',
            recipientId: 1,
            type: NotificationType::INFO,
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::SYSTEM,
            content: $this->content,
            createdAt: $this->now
        );

        $updated = $entity->markAsRead();

        $this->assertFalse($entity->isRead()); // Original unchanged
        $this->assertTrue($updated->isRead()); // New instance is read
        $this->assertNotSame($entity, $updated); // Instances are different
    }

    /**
     * Test that markAsUnread returns a new immutable instance.
     */
    public function test_mark_as_unread_returns_new_instance(): void
    {
        $entity = new NotificationEntity(
            id: 'notif_test_4',
            recipientId: 1,
            type: NotificationType::INFO,
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::SYSTEM,
            content: $this->content,
            readAt: $this->now,
            createdAt: $this->now
        );

        $updated = $entity->markAsUnread();

        $this->assertTrue($entity->isRead()); // Original unchanged
        $this->assertFalse($updated->isRead()); // New instance is unread
    }

    /**
     * Test soft delete sets deletedAt and marks entity inactive.
     */
    public function test_soft_delete_sets_deleted_at(): void
    {
        $entity = new NotificationEntity(
            id: 'notif_test_5',
            recipientId: 1,
            type: NotificationType::INFO,
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::SYSTEM,
            content: $this->content,
            createdAt: $this->now
        );

        $deleted = $entity->softDelete();

        $this->assertTrue($entity->isActive()); // Original unchanged
        $this->assertFalse($deleted->isActive()); // New instance is deleted
        $this->assertNotNull($deleted->getDeletedAt());
    }

    /**
     * Test conversion to array includes all relevant attributes.
     */
    public function test_to_array_conversion(): void
    {
        $entity = new NotificationEntity(
            id: 'notif_test_6',
            recipientId: 1,
            type: NotificationType::SUCCESS,
            priority: NotificationPriority::HIGH,
            category: NotificationCategory::USER,
            content: $this->content,
            readAt: $this->now,
            createdAt: $this->now
        );

        $array = $entity->toArray();

        $this->assertEquals('notif_test_6', $array['id']);
        $this->assertEquals(1, $array['recipient_id']);
        $this->assertEquals('success', $array['type']);
        $this->assertEquals('high', $array['priority']);
        $this->assertEquals('user', $array['category']);
        $this->assertEquals(__('notifications.test_title'), $array['content']['title']);
        $this->assertEquals(__('notifications.test_message'), $array['content']['body']);
        $this->assertTrue($array['is_read']);
        $this->assertTrue($array['is_active']);
    }

    /**
     * Test entity stores and returns metadata correctly.
     */
    public function test_entity_with_metadata(): void
    {
        $entity = new NotificationEntity(
            id: 'notif_test_7',
            recipientId: 1,
            type: NotificationType::INFO,
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::SYSTEM,
            content: $this->content,
            metadata: ['key' => 'value', 'count' => 5],
            createdAt: $this->now
        );

        $this->assertEquals(['key' => 'value', 'count' => 5], $entity->getMetadata());
    }
}
