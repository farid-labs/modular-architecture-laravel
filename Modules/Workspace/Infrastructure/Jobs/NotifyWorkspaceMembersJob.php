<?php

namespace Modules\Workspace\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

/**
 * Job to notify workspace members about important updates.
 */
class NotifyWorkspaceMembersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param  int  $workspaceId  The workspace ID to notify members of
     * @param  string  $notificationType  The type of notification (e.g., 'task_created', 'task_completed')
     * @param  array<string, mixed>  $data  Additional data for the notification
     * @param  int|null  $excludeUserId  User ID to exclude from notification (e.g., the actor)
     */
    public function __construct(
        private readonly int $workspaceId,
        private readonly string $notificationType,
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
            'notification_data' => $this->data,
        ]);

        // Send notification to each member
        foreach ($members as $member) {
            // Use the data property for notification content
            $this->sendNotification($member, $this->data);
        }

        Log::channel('domain')->info('Workspace members notified successfully', [
            'workspace_id' => $this->workspaceId,
        ]);
    }

    /**
     * Send notification to a single member.
     *
     * @param  array<string, mixed>  $data
     */
    private function sendNotification(UserModel $member, array $data): void
    {
        // Implement your notification logic here
        // Example: $member->notify(new WorkspaceNotification($this->notificationType, $data));

        Log::channel('domain')->debug('Sending notification to member', [
            'user_id' => $member->id,
            'notification_type' => $this->notificationType,
            'data' => $data,
        ]);
    }
}
