<?php

namespace Modules\Notifications\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Notifications\Domain\ValueObjects\NotificationContent;
use Modules\Notifications\Tests\TestCase;

/**
 * Unit tests for NotificationContent Value Object.
 *
 * Ensures correct construction, validation, URL formatting, and array conversion.
 *
 * @covers \Modules\Notifications\Domain\ValueObjects\NotificationContent
 */
class NotificationContentTest extends TestCase
{
    /**
     * Test creating a valid notification content object.
     */
    public function test_valid_content_can_be_created(): void
    {
        $content = new NotificationContent(
            title: __('notifications.test_title'),
            body: __('notifications.test_message'),
            actionLabel: __('notifications.view_action'),
            actionUrl: 'https://example.com'
        );

        $this->assertEquals(__('notifications.test_title'), $content->title());
        $this->assertEquals(__('notifications.test_message'), $content->body());
        $this->assertEquals(__('notifications.view_action'), $content->actionLabel());
        $this->assertEquals('https://example.com', $content->actionUrl());
    }

    /**
     * Test that optional fields can be null.
     */
    public function test_content_with_null_optional_fields(): void
    {
        $content = new NotificationContent(
            title: __('notifications.test_title'),
            body: __('notifications.test_message')
        );

        $this->assertNull($content->actionLabel());
        $this->assertNull($content->actionUrl());
    }

    /**
     * Test exceeding maximum title length throws an exception.
     */
    public function test_title_exceeds_maximum_length_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('notifications.errors.title_too_long', ['max' => 100]));

        new NotificationContent(
            title: str_repeat('a', 101),
            body: __('notifications.test_message')
        );
    }

    /**
     * Test exceeding maximum body length throws an exception.
     */
    public function test_body_exceeds_maximum_length_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('notifications.errors.body_too_long', ['max' => 500]));

        new NotificationContent(
            title: __('notifications.test_title'),
            body: str_repeat('a', 501)
        );
    }

    /**
     * Test invalid URL format throws an exception.
     */
    public function test_invalid_url_format_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('notifications.errors.invalid_url'));

        new NotificationContent(
            title: __('notifications.test_title'),
            body: __('notifications.test_message'),
            actionUrl: 'not-a-valid-url'
        );
    }

    /**
     * Test valid URL formats are accepted.
     */
    public function test_valid_url_formats(): void
    {
        $content = new NotificationContent(
            title: __('notifications.test_title'),
            body: __('notifications.test_message'),
            actionUrl: 'https://example.com/path?query=value'
        );

        $this->assertEquals('https://example.com/path?query=value', $content->actionUrl());
    }

    /**
     * Test conversion to array returns all fields correctly.
     */
    public function test_to_array_conversion(): void
    {
        $content = new NotificationContent(
            title: __('notifications.test_title'),
            body: __('notifications.test_message'),
            actionLabel: __('notifications.view_action'),
            actionUrl: 'https://example.com'
        );

        $array = $content->toArray();

        $this->assertEquals([
            'title' => __('notifications.test_title'),
            'body' => __('notifications.test_message'),
            'action_label' => __('notifications.view_action'),
            'action_url' => 'https://example.com',
        ], $array);
    }

    /**
     * Test title at maximum length (boundary validation).
     */
    public function test_title_at_maximum_length_is_valid(): void
    {
        $content = new NotificationContent(
            title: str_repeat('a', 100),
            body: __('notifications.test_message')
        );

        $this->assertEquals(100, mb_strlen($content->title()));
    }

    /**
     * Test body at maximum length (boundary validation).
     */
    public function test_body_at_maximum_length_is_valid(): void
    {
        $content = new NotificationContent(
            title: __('notifications.test_title'),
            body: str_repeat('a', 500)
        );

        $this->assertEquals(500, mb_strlen($content->body()));
    }
}
