<?php

namespace Modules\Notifications\Tests\Unit\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Notifications\Domain\Entities\NotificationEntity;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;
use Modules\Notifications\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for NotificationEntity.
 *
 * Ensures proper instantiation, immutability, state changes, and array conversion.
 *
 * @covers \Modules\Notifications\Domain\Entities\NotificationEntity
 */
#[CoversClass(NotificationEntityTest::class)]
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
            __('notifications.test_title'),
            __('notifications.test_message'),
            __('notifications.view_action'),
            'https://example.com'
        );
    }

    /**
     * Test that a notification entity can be created with all properties.
     */
    #[Test]
    public function test_entity_can_be_created(): void
    {
        $entity = new NotificationEntity(
            'notif_test_1',           // 1. id
            1,               // 2. recipientId
            NotificationType::INFO, // 3. type
            NotificationPriority::MEDIUM, // 4. priority
            NotificationCategory::SYSTEM, // 5. category
            $this->content,      // 6. content
            null,                 // 7. readAt (CarbonInterface|null) ← FIX: Was $this->now
            null,              // 8. deletedAt (CarbonInterface|null)
            $this->now,        // 9. createdAt (CarbonInterface|null) ← FIX: Add this
            null                // 10. metadata (?array)
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
    #[Test]
    public function test_entity_is_read_when_read_at_is_set(): void
    {
        $entity = new NotificationEntity(
            'notif_test_2',
            1,
            NotificationType::INFO,
            NotificationPriority::MEDIUM,
            NotificationCategory::SYSTEM,
            $this->content,
            $this->now,
            $this->now,
            $this->now,
            null
        );

        $this->assertTrue($entity->isRead());
        $this->assertEquals($this->now, $entity->getReadAt());
    }

    /**
     * Test that markAsRead returns a new immutable instance.
     */
    #[Test]
    public function test_mark_as_read_returns_new_instance(): void
    {
        $entity = new NotificationEntity(
            'notif_test_3',
            1,
            NotificationType::INFO,
            NotificationPriority::MEDIUM,
            NotificationCategory::SYSTEM,
            $this->content,
            null,
            null,
            $this->now,
            null
        );

        $updated = $entity->markAsRead();

        $this->assertFalse($entity->isRead()); // Original unchanged
        $this->assertTrue($updated->isRead()); // New instance is read
        $this->assertNotSame($entity, $updated); // Instances are different
    }

    /**
     * Test that markAsUnread returns a new immutable instance.
     */
    #[Test]
    public function test_mark_as_unread_returns_new_instance(): void
    {
        $entity = new NotificationEntity(
            'notif_test_4',
            1,
            NotificationType::INFO,
            NotificationPriority::MEDIUM,
            NotificationCategory::SYSTEM,
            $this->content,
            $this->now,
            $this->now
        );

        $updated = $entity->markAsUnread();

        $this->assertTrue($entity->isRead()); // Original unchanged
        $this->assertFalse($updated->isRead()); // New instance is unread
    }

    /**
     * Test soft delete sets deletedAt and marks entity inactive.
     */
    #[Test]
    public function test_soft_delete_sets_deleted_at(): void
    {
        $entity = new NotificationEntity(
            'notif_test_5',
            1,
            NotificationType::INFO,
            NotificationPriority::MEDIUM,
            NotificationCategory::SYSTEM,
            $this->content,
            $this->now
        );

        $deleted = $entity->softDelete();

        $this->assertTrue($entity->isActive()); // Original unchanged
        $this->assertFalse($deleted->isActive()); // New instance is deleted
        $this->assertNotNull($deleted->getDeletedAt());
    }

    /**
     * Test conversion to array includes all relevant attributes.
     */
    #[Test]
    public function test_to_array_conversion(): void
    {
        $entity = new NotificationEntity(
            'notif_test_6',
            1,
            NotificationType::SUCCESS,
            NotificationPriority::HIGH,
            NotificationCategory::USER,
            $this->content,
            $this->now,
            null,
            $this->now,
            null
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
    #[Test]
    public function test_entity_with_metadata(): void
    {
        $entity = new NotificationEntity(
            'notif_test_7',                          // 1. id
            1,                              // 2. recipientId
            NotificationType::INFO,                // 3. type
            NotificationPriority::MEDIUM,      // 4. priority
            NotificationCategory::SYSTEM,      // 5. category
            $this->content,                     // 6. content
            null,                                // 7. readAt (CarbonInterface|null)
            null,                             // 8. deletedAt (CarbonInterface|null)
            $this->now,                       // 9. createdAt (CarbonInterface|null)
            ['key' => 'value', 'count' => 5]   // 10. metadata (?array)
        );

        $this->assertEquals(['key' => 'value', 'count' => 5], $entity->getMetadata());
    }
}
