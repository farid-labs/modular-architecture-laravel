<?php

namespace Modules\Workspace\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Workspace\Domain\Events\TaskCompleted;
use Modules\Workspace\Domain\Events\TaskCreated;
use Modules\Workspace\Infrastructure\Jobs\NotifyWorkspaceMembersJob;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;

/**
 * Listener to send notifications for task events.
 * Subscribes to task created and completed events.
 */
class SendTaskNotificationListener
{
    /**
     * Handle task created event.
     *
     * @param  TaskCreated  $event  The task created event
     */
    public function onTaskCreated(TaskCreated $event): void
    {
        Log::channel('domain')->info('Task created notification triggered', [
            'task_id' => $event->task->getId(),
            'project_id' => $event->task->getProjectId(),
        ]);

        // Dispatch notification job
        dispatch(new NotifyWorkspaceMembersJob(
            $this->getWorkspaceIdFromProject($event->task->getProjectId()),
            'task_created',
            ['task_id' => $event->task->getId(), 'task_title' => $event->task->getTitleVO()->value()],
            $event->actorId
        ));
    }

    /**
     * Handle task completed event.
     *
     * @param  TaskCompleted  $event  The task completed event
     */
    public function onTaskCompleted(TaskCompleted $event): void
    {
        Log::channel('domain')->info('Task completed notification triggered', [
            'task_id' => $event->task->getId(),
        ]);

        // Dispatch notification job
        dispatch(new NotifyWorkspaceMembersJob(
            $this->getWorkspaceIdFromProject($event->task->getProjectId()),
            'task_completed',
            ['task_id' => $event->task->getId(), 'task_title' => $event->task->getTitleVO()->value()],
            $event->actorId
        ));
    }

    /**
     * Get workspace ID from project ID.
     *
     * @param  int  $projectId  The project ID
     * @return int
     */
    private function getWorkspaceIdFromProject(int $projectId): int
    {
        $project = ProjectModel::find($projectId);

        if ($project === null) {
            return 0;
        }

        return $project->workspace_id;
    }

    /**
     * Register the listeners for the subscriber.
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
