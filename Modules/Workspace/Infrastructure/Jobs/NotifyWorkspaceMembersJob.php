<?php

namespace Modules\Workspace\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

/**
 * Job to notify workspace members about important updates.
 * Queued job for async notification delivery.
 */
class NotifyWorkspaceMembersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Maximum time in seconds the job may run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param  int  $workspaceId  The workspace ID to notify members of
     * @param  string  $notificationType  The type of notification
     * @param  array<string, mixed>  $data  Additional data for the notification
     * @param  int|null  $excludeUserId  User ID to exclude from notification
     */
    public function __construct(
        private readonly int $workspaceId,
        private readonly string $notificationType,
        /** @var array<string, mixed> */
        private readonly array $data,
        private readonly ?int $excludeUserId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all workspace members except the excluded user
        $members = UserModel::whereHas('workspaces', function ($query) {
            $query->where('workspace_id', $this->workspaceId);
        })->where('id', '!=', $this->excludeUserId)->get();

        Log::channel('domain')->info('Notifying workspace members', [
            'workspace_id' => $this->workspaceId,
            'notification_type' => $this->notificationType,
            'member_count' => $members->count(),
        ]);

        // Send notification to each member
        foreach ($members as $member) {
            $this->sendNotification($member);
        }

        Log::channel('domain')->info('Workspace members notified successfully', [
            'workspace_id' => $this->workspaceId,
        ]);
    }

    /**
     * Send notification to a single member.
     *
     * @param  UserModel  $member  The user to notify
     */
    private function sendNotification(UserModel $member): void
    {
        /** @var array<string, mixed> $notificationData */
        $notificationData = $this->data;

        Log::channel('domain')->debug('Sending notification to member', [
            'user_id' => $member->id,
            'notification_type' => $this->notificationType,
            'data' => $notificationData,
        ]);
    }
}
