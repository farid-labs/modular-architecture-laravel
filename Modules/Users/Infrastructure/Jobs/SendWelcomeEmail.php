<?php

namespace Modules\Users\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Users\Infrastructure\Mail\WelcomeEmail as WelcomeEmailMailable;

/**
 * Class SendWelcomeEmail
 *
 * Queued job responsible for sending a welcome email
 * after successful user registration.
 *
 * Architecture Notes:
 * - Lives in the Infrastructure layer (side-effect operation).
 * - Implements ShouldQueue for asynchronous execution.
 * - Passes only primitive data (userId) to avoid model serialization issues.
 * - Designed to be retry-safe and idempotent.
 *
 * Reliability Features:
 * - Configurable retry attempts
 * - Exponential backoff strategy
 * - Graceful handling when user no longer exists
 * - Structured logging for observability
 *
 * @package Modules\Users\Infrastructure\Jobs
 */
class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum execution time (seconds) before the job times out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Number of retry attempts before marking the job as failed.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Backoff intervals (seconds) between retry attempts.
     * Implements exponential retry strategy.
     *
     * Example: 10s → 30s → 60s
     *
     * @var array<int>
     */
    public $backoff = [10, 30, 60];

    /**
     * Automatically delete the job if related models are missing.
     *
     * Prevents unnecessary failures if the user
     * was deleted before job execution.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * Important:
     * Only primitive values are passed to the job constructor.
     * Avoid passing Eloquent models to prevent serialization issues.
     *
     * @param int $userId ID of the registered user
     * @param string $locale Preferred email locale (default: 'en')
     */
    public function __construct(
        public int $userId,
        public string $locale = 'en'
    ) {}

    /**
     * Execute the job.
     *
     * Responsibilities:
     * - Retrieve the user from persistence layer
     * - Send localized welcome email
     * - Log success or missing user scenario
     *
     * @return void
     */
    public function handle(): void
    {
        $user = UserModel::find($this->userId);

        if (!$user) {
            Log::warning("User {$this->userId} not found for welcome email");
            return;
        }

        Mail::to($user->email)
            ->locale($this->locale)
            ->send(new WelcomeEmailMailable($user));

        Log::info("Welcome email sent to user {$user->email}");
    }

    /**
     * Handle a job failure after all retry attempts.
     *
     * This method is triggered automatically by Laravel
     * when the job exceeds the maximum retry limit.
     *
     * Recommended actions:
     * - Notify administrators
     * - Persist structured failure logs
     * - Trigger monitoring/alerting systems
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Welcome email job failed for user {$this->userId}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Optional:
        // Dispatch alert notification
        // Persist into custom failed_jobs_log table
        // Send to monitoring service (Sentry, Bugsnag, etc.)
    }
}
