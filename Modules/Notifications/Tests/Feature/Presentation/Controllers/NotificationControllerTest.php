<?php

namespace Modules\Notifications\Tests\Feature\Presentation\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Notifications\Infrastructure\Jobs\DispatchNotificationJob;
use Modules\Notifications\Infrastructure\Persistence\Models\NotificationModel;
use Modules\Notifications\Presentation\Controllers\NotificationController;
use Modules\Notifications\Tests\TestCase;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(NotificationController::class)]
class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected UserModel $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserModel::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    #[Test]
    public function authenticated_user_can_list_notifications(): void
    {
        // FIX: Factory now generates proper UUIDs automatically
        NotificationModel::factory()->create([
            'notifiable_id' => $this->user->id,
            'data' => ['title' => __('notifications.test_title'), 'message' => __('notifications.test_message')],
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson(route('notifications.index'));

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
                    'created_at',
                ]],
                'meta' => ['current_page', 'per_page', 'total'],
                'message',
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->getJson(route('notifications.index'));
        $response->assertUnauthorized();
    }

    #[Test]
    public function can_get_unread_notification_count(): void
    {
        NotificationModel::factory()->count(2)->create([
            'notifiable_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson(route('notifications.unread-count'));

        $response->assertOk()
            ->assertJson(['data' => ['unread_count' => 2]]);
    }

    #[Test]
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
        ])->postJson(route('notifications.send'), $payload);

        $response->assertCreated()
            ->assertJson(['message' => __('notifications.created')]);

        Queue::assertPushed(DispatchNotificationJob::class, function ($job) {
            return $job instanceof DispatchNotificationJob;
        });
    }

    #[Test]
    public function send_notification_validation_errors(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson(route('notifications.send'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['recipient_id', 'type', 'title', 'message']);
    }

    #[Test]
    public function can_mark_single_notification_as_read(): void
    {
        $notification = NotificationModel::factory()->create([
            'notifiable_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->patchJson(route('notifications.mark-read', ['id' => $notification->id]));

        $response->assertOk()
            ->assertJson(['message' => __('notifications.marked_read')]);

        $updatedNotification = NotificationModel::find($notification->id);
        $this->assertNotNull($updatedNotification);
        $this->assertNotNull($updatedNotification->read_at);
    }

    #[Test]
    public function can_mark_all_notifications_as_read(): void
    {
        NotificationModel::factory()->count(2)->create([
            'notifiable_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson(route('notifications.mark-all-read'));

        $response->assertOk()
            ->assertJsonStructure(['data' => ['marked_count'], 'message']);
    }

    #[Test]
    public function can_delete_notification(): void
    {
        $notification = NotificationModel::factory()->create([
            'notifiable_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->deleteJson(route('notifications.destroy', ['id' => $notification->id]));

        $response->assertOk()
            ->assertJson(['message' => __('notifications.deleted')]);

        $this->assertSoftDeleted('notifications', ['id' => $notification->id]);
    }

    #[Test]
    public function user_cannot_access_notifications_of_other_users(): void
    {
        $otherUser = UserModel::factory()->create();

        $notification = NotificationModel::factory()->create([
            'notifiable_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->patchJson(route('notifications.mark-read', ['id' => $notification->id]));

        $response->assertNotFound();
    }

    #[Test]
    public function can_filter_notifications_by_type(): void
    {
        $notif1 = NotificationModel::factory()->create(['notifiable_id' => $this->user->id, 'type' => 'success']);
        NotificationModel::factory()->create(['notifiable_id' => $this->user->id, 'type' => 'error']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson(route('notifications.index', [
            'type' => 'success',
        ]));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('success', $data[0]['type']);
    }

    #[Test]
    public function can_filter_only_unread_notifications(): void
    {
        $unread = NotificationModel::factory()->create(['notifiable_id' => $this->user->id, 'read_at' => null]);
        NotificationModel::factory()->create(['notifiable_id' => $this->user->id, 'read_at' => now()]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson(route('notifications.index', [
            'unread_only' => true,
        ]));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($unread->id, $data[0]['id']);
    }
}
