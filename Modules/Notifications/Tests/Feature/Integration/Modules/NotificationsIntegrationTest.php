<?php

namespace Tests\Feature\Integration\Modules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Notifications\Infrastructure\Jobs\DispatchNotificationJob;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration tests to verify cross-module communication.
 * Ensures that the Users module triggers the Notifications module correctly
 * when a user is registered.
 */
class NotificationsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_user_registration_triggers_notification_job(): void
    {
        Queue::fake();

        $payload = [
            'name' => 'Integration User',
            'email' => 'integration@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('users.auth.register'), $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'email'],
                'token',
            ]);

        // Get the created user
        $user = UserModel::where('email', $payload['email'])->first();
        $this->assertNotNull($user);

        // The job is pushed by the listener → we only need to verify it was dispatched
        Queue::assertPushed(DispatchNotificationJob::class, function ($job) {
            return $job->notificationId !== null;
        });

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
        ]);
    }
}
