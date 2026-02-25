<?php

namespace Modules\Workspace\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\Application\DTOs\SendNotificationDTO;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Workspace\Domain\Events\TaskCompleted;
use Modules\Workspace\Domain\Events\TaskCreated;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;

/**
 * Listener responsible for sending notifications related to task events.
 *
 * This listener delegates task notifications to the central NotificationService,
 * ensuring workspace members are notified asynchronously without coupling to
 * the Workspace domain logic.
 */
class SendTaskNotificationListener
{
    /**
     * Inject the NotificationService.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle TaskCreated event.
     *
     * Sends a notification to relevant workspace members when a new task is created.
     * Uses localized strings from /lang/en/workspaces.php.
     *
     * @param TaskCreated $event
     * @return void
     */
    public function onTaskCreated(TaskCreated $event): void
    {
        $workspace = $this->getWorkspaceFromProject($event->task->getProjectId());

        // If workspace not found, abort notification
        if (!$workspace) {
            Log::channel('domain')->warning('Workspace not found for task creation', [
                'task_id' => $event->task->getId(),
                'project_id' => $event->task->getProjectId(),
            ]);
            return;
        }

        // Log notification event for audit purposes
        Log::channel('domain')->info('Task created notification triggered', [
            'task_id' => $event->task->getId(),
            'workspace_id' => $workspace->id,
        ]);

        // Prepare notification DTO for project owner (example)
        $dto = SendNotificationDTO::forUser(
            userId: $workspace->owner_id,
            type: NotificationType::INFO,
            title: __('workspaces.task_created_title'), // e.g., "New Task Created"
            message: __('workspaces.task_created_message', [
                'title' => $event->task->getTitleVO()->value()
            ]), // e.g., "Task 'Design Homepage' has been created"
            channels: [
                NotificationChannel::DATABASE, // Persist in database
                NotificationChannel::PUSH      // Send via push notifications
            ],
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::PROJECT,
            actionUrl: route('tasks.show', $event->task->getId()), // Link to task details
            metadata: [
                'task_id' => $event->task->getId(),
                'workspace_id' => $workspace->id
            ]
        );

        // Dispatch notification via central service
        $this->notificationService->sendNotification($dto);
    }

    /**
     * Handle TaskCompleted event.
     *
     * Sends notifications to workspace members when a task is completed.
     * Currently logs the event; can be extended to notify multiple members.
     *
     * @param TaskCompleted $event
     * @return void
     */
    public function onTaskCompleted(TaskCompleted $event): void
    {
        Log::channel('domain')->info('Task completed notification triggered', [
            'task_id' => $event->task->getId(),
        ]);

        // TODO: Build DTO for completion notifications using /lang/en/workspaces.php
        // Example:
        // $dto = SendNotificationDTO::forUser(...);
        // $this->notificationService->sendNotification($dto);
    }

    /**
     * Retrieve the workspace associated with a given project ID.
     *
     * @param int $projectId
     * @return WorkspaceModel|null
     */
    private function getWorkspaceFromProject(int $projectId): ?WorkspaceModel
    {
        // Fetch the first workspace that contains the given project
        return WorkspaceModel::whereHas('projects', fn($q) => $q->where('id', $projectId))
            ->first();
    }

    /**
     * Subscribe to workspace domain events.
     *
     * Registers event-to-method mappings for Laravel's event system.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            TaskCreated::class => 'onTaskCreated',
            TaskCompleted::class => 'onTaskCompleted',
        ];
    }
}
