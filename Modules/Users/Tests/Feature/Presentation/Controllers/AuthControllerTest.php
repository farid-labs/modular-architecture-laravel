<?php

namespace Modules\Users\Tests\Feature\Presentation\Controllers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Notifications\Infrastructure\Jobs\DispatchNotificationJob;
use Modules\Users\Domain\Events\UserCreated;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Users\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for AuthController.
 * Covers user registration and dispatching of welcome email notifications.
 */
#[CoversClass(AuthControllerTest::class)]
class AuthControllerTest extends TestCase
{
    #[Test]
    public function registration_dispatches_welcome_job_and_returns_token(): void
    {
        // Arrange: Fake queue and event system
        Queue::fake();
        // Event::fake([UserCreated::class]); // ← REMOVE THIS LINE

        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('users.auth.register'), $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'email'],
                'token',
            ]);

        $user = UserModel::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // DispatchNotificationJob has: notificationId, channel, locale (NOT recipientId)
        Queue::assertPushed(DispatchNotificationJob::class, function ($job) {
            return $job->notificationId !== null
                && $job->channel !== null;
        });

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }
}
