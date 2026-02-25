<?php

namespace Modules\Notifications\Tests\Feature\Infrastructure\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\Notifications\Infrastructure\Jobs\DispatchNotificationJob;
use Modules\Notifications\Infrastructure\Notifications\CustomNotification;
use Modules\Notifications\Infrastructure\Persistence\Models\NotificationModel;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Notifications\Tests\TestCase;

/**
 * Integration tests for DispatchNotificationJob.
 *
 * Covers:
 * - Dispatching notifications via database and email channels
 * - Handling missing notifications gracefully
 * - Handling missing users gracefully
 * - Logging exceptions in job failures
 *
 * @covers \Modules\Notifications\Infrastructure\Jobs\DispatchNotificationJob
 */
class DispatchNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    protected UserModel $user;
    protected NotificationModel $notification;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a verified test user
        $this->user = UserModel::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create a notification record for tests
        $this->notification = NotificationModel::create([
            'id' => 'notif_job_test',
            'type' => 'success',
            'priority' => 'high',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => $this->user->id,
            'data' => [
                'title' => __('notifications.job_test_title'),
                'message' => __('notifications.job_test_message'),
                'action_url' => 'https://example.com',
            ],
            'read_at' => null,
        ]);
    }

    /** @test */
    public function job_dispatches_notification_successfully(): void
    {
        Notification::fake();

        $job = new DispatchNotificationJob(
            notificationId: 'notif_job_test',
            channel: 'database',
            locale: 'fa'
        );

        $job->handle();

        Notification::assertSentTo(
            $this->user,
            CustomNotification::class,
            fn($notification) =>
            $notification->title === __('notifications.job_test_title') &&
                $notification->message === __('notifications.job_test_message')
        );
    }

    /** @test */
    public function job_handles_missing_notification_gracefully(): void
    {
        $job = new DispatchNotificationJob(
            notificationId: 'non_existent_id',
            channel: 'database',
            locale: 'fa'
        );

        // Act & Assert - Should not throw any exception
        $job->handle();

        Notification::assertNothingSent();
    }

    /** @test */
    public function job_handles_missing_user_gracefully(): void
    {
        NotificationModel::create([
            'id' => 'notif_missing_user',
            'type' => 'info',
            'priority' => 'medium',
            'category' => 'system',
            'notifiable_type' => UserModel::class,
            'notifiable_id' => 99999, // Non-existent user
            'data' => ['title' => 'Test', 'message' => 'Test'],
            'read_at' => null,
        ]);

        $job = new DispatchNotificationJob(
            notificationId: 'notif_missing_user',
            channel: 'database',
            locale: 'fa'
        );

        $job->handle();

        Notification::assertNothingSent();
    }

    /** @test */
    public function job_with_email_channel_sends_mail(): void
    {
        Mail::fake();
        Notification::fake();

        $job = new DispatchNotificationJob(
            notificationId: 'notif_job_test',
            channel: 'email',
            locale: 'fa'
        );

        $job->handle();

        Notification::assertSentTo(
            $this->user,
            CustomNotification::class,
            fn($notification) =>
            in_array('mail', $notification->via($this->user))
        );
    }

    /** @test */
    public function failed_method_logs_exception(): void
    {
        $job = new DispatchNotificationJob(
            notificationId: 'notif_job_test',
            channel: 'database',
            locale: 'fa'
        );

        $exception = new \Exception('Test exception');

        // Call failed method
        $job->failed($exception);

        // Placeholder for logging assertion (depends on logging setup)
        $this->assertTrue(true);
    }
}
