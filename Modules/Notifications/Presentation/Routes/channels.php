<?php

use Illuminate\Support\Facades\Broadcast;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

/**
 * Broadcast channel routes for the Notifications module.
 *
 * This file defines private and presence channels to authorize
 * users for real-time notification broadcasts. Only authenticated
 * users who are permitted can listen to these channels.
 */

/**
 * Private channel for user-specific notifications.
 *
 * Each user receives notifications on their own private channel.
 * Ensures only the intended recipient can listen to their notifications.
 *
 * Example channel name: notifications.123 (for user ID 123)
 *
 * @param UserModel $user The currently authenticated user
 * @param int $userId The ID of the channel
 * @return bool True if authorized to listen
 */
Broadcast::channel('notifications.{userId}', function (UserModel $user, int $userId) {
    // Only allow the authenticated user to access their own notifications
    return (int) $user->id === $userId;
});

/**
 * Private channel for workspace-specific notifications.
 *
 * Members of a workspace can listen to its private notification channel.
 * Example channel name: workspace.42 (for workspace ID 42)
 *
 * @param UserModel $user The currently authenticated user
 * @param int $workspaceId The ID of the workspace
 * @return bool True if the user belongs to the workspace
 */
Broadcast::channel('workspace.{workspaceId}', function (UserModel $user, int $workspaceId) {
    // Check if the authenticated user is a member of the workspace
    return $user->workspaces()->where('workspace_id', $workspaceId)->exists();
});
