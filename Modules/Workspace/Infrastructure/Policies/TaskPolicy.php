<?php

namespace Modules\Workspace\Infrastructure\Policies;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Entities\TaskEntity;

/**
 * Authorization policy for Task entities.
 *
 * Defines access control rules for task-related operations.
 * Determines whether a user can perform specific actions on a task
 * based on their membership in the task's project workspace.
 *
 * Policy Methods:
 * - view: Check if user can view task details
 * - update: Check if user can modify task properties
 * - complete: Check if user can mark task as completed
 * - comment: Check if user can add comments to task
 *
 * @see UserModel The authenticated user model
 * @see TaskEntity The task domain entity being authorized
 */
class TaskPolicy
{
    /**
     * Determine if the user can view the task.
     *
     * Users can view tasks if they are members of the project's workspace.
     * This ensures only authorized team members can access task information.
     *
     * @param  UserModel  $user  The authenticated user attempting to view the task
     * @param  TaskEntity  $task  The task entity being accessed
     * @return bool True if user is authorized to view the task, false otherwise
     */
    public function view(UserModel $user, TaskEntity $task): bool
    {
        return $this->isMemberOfProject($user, $task);
    }

    /**
     * Determine if the user can update the task.
     *
     * Users can update tasks if they are members of the project's workspace.
     * This allows team collaboration while preventing unauthorized modifications.
     *
     * @param  UserModel  $user  The authenticated user attempting to update the task
     * @param  TaskEntity  $task  The task entity being modified
     * @return bool True if user is authorized to update the task, false otherwise
     */
    public function update(UserModel $user, TaskEntity $task): bool
    {
        return $this->isMemberOfProject($user, $task);
    }

    /**
     * Determine if the user can mark the task as completed.
     *
     * Users can complete tasks if they are members of the project's workspace.
     * This ensures only team members can change task status.
     *
     * @param  UserModel  $user  The authenticated user attempting to complete the task
     * @param  TaskEntity  $task  The task entity being completed
     * @return bool True if user is authorized to complete the task, false otherwise
     */
    public function complete(UserModel $user, TaskEntity $task): bool
    {
        return $this->isMemberOfProject($user, $task);
    }

    /**
     * Determine if the user can add comments to the task.
     *
     * Users can comment on tasks if they are members of the project's workspace.
     * This enables team discussion while restricting access to authorized members.
     *
     * @param  UserModel  $user  The authenticated user attempting to comment
     * @param  TaskEntity  $task  The task entity being commented on
     * @return bool True if user is authorized to comment, false otherwise
     */
    public function comment(UserModel $user, TaskEntity $task): bool
    {
        return $this->isMemberOfProject($user, $task);
    }

    /**
     * Check if the user is a member of the task's project workspace.
     *
     * This helper method centralizes the membership check logic.
     * Currently returns true for all authenticated users (placeholder).
     * TODO: Implement actual workspace membership verification.
     *
     * @param  UserModel  $user  The user to check membership for
     * @param  TaskEntity  $task  The task whose project workspace to check
     * @return bool True if user is a member, false otherwise
     *
     * @todo Implement actual workspace membership verification logic
     * @todo Query workspace_members table to verify membership
     */
    private function isMemberOfProject(UserModel $user, TaskEntity $task): bool
    {
        // TODO: Implement actual membership check
        // Example implementation:
        // $project = $this->workspaceRepository->findProjectById($task->getProjectId());
        // return $this->workspaceRepository->isUserMemberOfWorkspace(
        //     $project->getWorkspaceId(),
        //     $user->id
        // );

        return true; // Placeholder - always allows for development
    }
}
