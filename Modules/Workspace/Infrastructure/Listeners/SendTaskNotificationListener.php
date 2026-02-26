<?php

namespace Modules\Workspace\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\Application\DTOs\SendNotificationDTO;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationChannel;
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
     */
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle TaskCreated event.
     *
     * Sends a notification to relevant workspace members when a new task is created.
     * Uses localized strings from /lang/en/workspaces.php.
     */
    public function onTaskCreated(TaskCreated $event): void
    {
        $workspace = $this->getWorkspaceFromProject($event->task->getProjectId());

        // If workspace not found, abort notification
        if (! $workspace) {
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
            userId: $workspace->owner_id,                                    // 1. int
            type: NotificationType::INFO,                                    // 2. NotificationType
            title: __('workspaces.task_created_title'),                      // 3. string
            message: __('workspaces.task_created_message', [                 // 4. string
                'title' => $event->task->getTitleVO()->value(),
            ]),
            channels: [                                                      // 5. array
                NotificationChannel::DATABASE,
                NotificationChannel::PUSH,
            ],
            priority: NotificationPriority::MEDIUM,                          // 6. NotificationPriority
            category: NotificationCategory::PROJECT,                         // 7. NotificationCategory
            actionUrl: route('tasks.show', $event->task->getId()),           // 8. ?string
            actionLabel: __('workspaces.view_task'),                         // 9. ?string ← ADD THIS
            metadata: [                                                      // 10. ?array
                'task_id' => $event->task->getId(),
                'workspace_id' => $workspace->id,
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
     */
    public function onTaskCompleted(TaskCompleted $event): void
    {
        Log::channel('domain')->info('Task completed notification triggered', [
            'task_id' => $event->task->getId(),
            'actor_id' => $event->actorId,
        ]);

        // Get workspace from project to determine notification recipients
        $workspace = $this->getWorkspaceFromProject($event->task->getProjectId());

        // If workspace not found, abort notification
        if (! $workspace) {
            Log::channel('domain')->warning('Workspace not found for task completion', [
                'task_id' => $event->task->getId(),
                'project_id' => $event->task->getProjectId(),
            ]);

            return;
        }

        // Log notification event for audit purposes
        Log::channel('domain')->info('Task completed notification triggered', [
            'task_id' => $event->task->getId(),
            'workspace_id' => $workspace->id,
            'completed_by' => $event->actorId,
        ]);

        // Prepare notification DTO for project owner (example)
        // Notify workspace owner about task completion
        $dto = SendNotificationDTO::forUser(
            userId: $workspace->owner_id,
            type: NotificationType::SUCCESS,
            title: __('workspaces.task_completed_title'), // e.g., "Task Completed"
            message: __('workspaces.task_completed_message', [
                'title' => $event->task->getTitleVO()->value(),
                'completed_by' => $event->actorId,
            ]), // e.g., "Task 'Design Homepage' has been completed"
            channels: [
                NotificationChannel::DATABASE, // Persist in database
                NotificationChannel::PUSH,      // Send via push notifications
            ],
            priority: NotificationPriority::MEDIUM,
            category: NotificationCategory::TASK,
            actionUrl: route('tasks.show', $event->task->getId()), // Link to task details
            actionLabel: __('workspaces.view_task'), // e.g., "View Task"
            metadata: [
                'task_id' => $event->task->getId(),
                'workspace_id' => $workspace->id,
                'event' => 'task_completed',
                'completed_by' => $event->actorId,
            ]
        );

        // Dispatch notification via central service
        $this->notificationService->sendNotification($dto);

        // Optional: Notify task assignee if different from completer
        if ($event->task->getAssignedTo() !== null && $event->task->getAssignedTo() !== $event->actorId) {
            $assigneeDto = SendNotificationDTO::forUser(
                userId: $event->task->getAssignedTo(),                                      // 1. int
                type: NotificationType::INFO,                                               // 2. NotificationType
                title: __('workspaces.task_assigned_completed_title'),                 // 3. string
                message: __('workspaces.task_assigned_completed_message', [   // 4. string
                    'title' => $event->task->getTitleVO()->value(),
                ]),
                channels: [NotificationChannel::DATABASE],                                  // 5. array
                priority: NotificationPriority::LOW,                                        // 6. NotificationPriority
                category: NotificationCategory::TASK,                                       // 7. NotificationCategory
                actionUrl: route('tasks.show', $event->task->getId()),    // 8. ?string
                actionLabel: __('workspaces.view_task'),                               // 9. ?string ← ADD THIS
                metadata: [                                                                 // 10. ?array
                    'task_id' => $event->task->getId(),
                    'workspace_id' => $workspace->id,
                    'event' => 'task_assigned_completed',
                ]
            );

            $this->notificationService->sendNotification($assigneeDto);
        }
    }

    /**
     * Retrieve the workspace associated with a given project ID.
     */
    private function getWorkspaceFromProject(int $projectId): ?WorkspaceModel
    {
        // Fetch the first workspace that contains the given project
        return WorkspaceModel::whereHas('projects', fn ($q) => $q->where('id', $projectId))
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
