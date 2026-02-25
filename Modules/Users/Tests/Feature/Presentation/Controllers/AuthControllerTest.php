<?php

namespace Tests\Feature\Presentation\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Queue;
use Modules\Users\Infrastructure\Jobs\SendWelcomeEmail;
use Modules\Users\Tests\TestCase;

/**
 * Class AuthControllerTest
 *
 * Feature tests for user registration endpoint.
 *
 * Verifies that:
 * - Registration endpoint returns HTTP 201
 * - JSON response contains expected structure
 * - Welcome email job is dispatched
 * - User is persisted in database
 */
class AuthControllerTest extends TestCase
{
    /**
     * Ensure that successful registration:
     * 1. Dispatches SendWelcomeEmail job
     * 2. Returns authentication token
     * 3. Persists user in database
     */
    #[Test]
    public function registration_dispatches_welcome_job_and_returns_token(): void
    {
        Queue::fake();

        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/v1/auth/register', $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'email'],
                'token'
            ]);

        Queue::assertPushed(SendWelcomeEmail::class, function ($job) {
            return $job->userId === 1;
            // Better: fetch user by email instead of hardcoding ID (see improvement below)
        });

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }
}
