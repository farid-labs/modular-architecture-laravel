<?php

namespace Tests\Feature\Integration\Modules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Notifications\Infrastructure\Jobs\DispatchNotificationJob;
use Modules\Users\Domain\Events\UserCreated;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Tests\TestCase;

/**
 * Integration tests for the Notifications module.
 *
 * These tests verify that cross-module communication works as expected:
 *  - When a user registers via the Users module, the Notifications module
 *    should receive a trigger to create a welcome notification.
 *  - Ensures the Notification job is queued correctly.
 *  - Confirms that database entries are persisted for both users and notifications.
 */
class NotificationsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that registering a new user triggers a notification job.
     *
     * This test performs a full integration check:
     *  - Posts a registration request to the API.
     *  - Verifies the UserCreated event is dispatched.
     *  - Asserts the DispatchNotificationJob is pushed to the queue.
     *  - Confirms the user record exists in the database.
     *  - Confirms a notification record is created for the new user.
     *
     * @return void
     */
    public function test_user_registration_triggers_notification_job(): void
    {
        // Arrange: Fake the queue and events to isolate this test
        Queue::fake();
        Event::fake([UserCreated::class]);

        $payload = [
            'name' => 'Integration User',
            'email' => 'integration@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act: Submit registration request
        $response = $this->postJson('/api/v1/auth/register', $payload);

        // Assert: API response returns HTTP 201 Created
        $response->assertCreated();

        // Assert: UserCreated event was dispatched
        Event::assertDispatched(UserCreated::class);

        // Assert: Notification job was pushed to the queue
        Queue::assertPushed(DispatchNotificationJob::class, function ($job) {
            // Optional: Add more precise checks on job's notificationId or payload
            return true;
        });

        // Assert: User is persisted in the database
        $this->assertDatabaseHas('users', [
            'email' => 'integration@test.com',
        ]);

        // Assert: Notification record exists for the user
        // Note: If using a queued job, set QUEUE_CONNECTION=sync for testing
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => UserModel::class,
            'type' => 'success', // Welcome notification type
        ]);
    }
}
