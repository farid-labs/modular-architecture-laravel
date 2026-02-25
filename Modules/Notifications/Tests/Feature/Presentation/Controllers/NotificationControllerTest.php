<?php

namespace Modules\Notifications\Tests\Feature\Presentation\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Notifications\Infrastructure\Jobs\DispatchNotificationJob;
use Modules\Notifications\Infrastructure\Persistence\Models\NotificationModel;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Notifications\Tests\TestCase;
use Laravel\Sanctum\Sanctum;

/**
 * Feature tests for NotificationController API endpoints.
 *
 * Covers:
 * - Listing notifications
 * - Unread count
 * - Sending notifications
 * - Marking read/unread
 * - Deleting notifications
 * - Authorization enforcement
 *
 * @covers \Modules\Notifications\Presentation\Controllers\NotificationController
 */
class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected UserModel $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate test user
        $this->user = UserModel::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function authenticated_user_can_list_notifications(): void
    {
        NotificationModel::factory()->create([
            'id' => 'notif_test_1',
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'type',
                    'priority',
                    'category',
                    'title',
                    'message',
                    'is_read',
                    'is_active',
                    'created_at'
                ]],
                'meta' => ['current_page', 'per_page', 'total'],
                'message',
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->getJson('/api/v1/notifications');
        $response->assertUnauthorized();
    }

    /** @test */
    public function can_get_unread_notification_count(): void
    {
        NotificationModel::factory()->count(2)->create([
            'notifiable_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJson(['data' => ['unread_count' => 2]]);
    }

    /** @test */
    public function sending_notification_queues_dispatch_job(): void
    {
        Queue::fake();

        $payload = [
            'recipient_id' => $this->user->id,
            'type' => 'success',
            'title' => __('notifications.test_title'),
            'message' => __('notifications.test_message'),
            'channels' => ['database', 'email'],
            'priority' => 'medium',
            'category' => 'system',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/notifications/send', $payload);

        $response->assertCreated()
            ->assertJson(['message' => __('notifications.created')]);

        Queue::assertPushed(DispatchNotificationJob::class, fn($job) => $job->notificationId !== null);
    }

    /** @test */
    public function send_notification_validation_errors(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/notifications/send', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['recipient_id', 'type', 'title', 'message']);
    }

    /** @test */
    public function can_mark_single_notification_as_read(): void
    {
        NotificationModel::factory()->create([
            'id' => 'notif_mark_read',
            'notifiable_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/notifications/notif_mark_read/read');

        $response->assertOk()
            ->assertJson(['message' => __('notifications.marked_read')]);

        $this->assertNotNull(NotificationModel::find('notif_mark_read')->read_at);
    }

    /** @test */
    public function can_mark_all_notifications_as_read(): void
    {
        NotificationModel::factory()->count(2)->create([
            'notifiable_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/notifications/mark-all-read');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['marked_count'], 'message']);
    }

    /** @test */
    public function can_delete_notification(): void
    {
        NotificationModel::factory()->create([
            'id' => 'notif_delete',
            'notifiable_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->deleteJson('/api/v1/notifications/notif_delete');

        $response->assertOk()
            ->assertJson(['message' => __('notifications.deleted')]);

        $this->assertSoftDeleted('notifications', ['id' => 'notif_delete']);
    }

    /** @test */
    public function user_cannot_access_notifications_of_other_users(): void
    {
        $otherUser = UserModel::factory()->create();

        NotificationModel::factory()->create([
            'id' => 'notif_other',
            'notifiable_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->patchJson('/api/v1/notifications/notif_other/read');

        $response->assertNotFound();
    }

    /** @test */
    public function can_filter_notifications_by_type(): void
    {
        NotificationModel::factory()->create(['id' => 'notif_type_1', 'notifiable_id' => $this->user->id, 'type' => 'success']);
        NotificationModel::factory()->create(['id' => 'notif_type_2', 'notifiable_id' => $this->user->id, 'type' => 'error']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/notifications?type=success');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('success', $data[0]['type']);
    }

    /** @test */
    public function can_filter_only_unread_notifications(): void
    {
        NotificationModel::factory()->create(['id' => 'notif_unread', 'notifiable_id' => $this->user->id, 'read_at' => null]);
        NotificationModel::factory()->create(['id' => 'notif_read', 'notifiable_id' => $this->user->id, 'read_at' => now()]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/notifications?unread_only=true');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('notif_unread', $data[0]['id']);
    }
}
