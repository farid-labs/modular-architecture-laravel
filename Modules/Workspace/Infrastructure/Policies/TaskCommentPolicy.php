<?php

namespace Modules\Workspace\Infrastructure\Policies;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;

/**
 * Authorization policy for Task Comment entities.
 *
 * Defines access control rules for task comment operations.
 * Enforces ownership-based authorization to ensure users can only
 * modify or delete their own comments.
 *
 * Policy Methods:
 * - update: Check if user can edit their own comment
 * - delete: Check if user can remove their own comment
 *
 * Authorization Strategy:
 * All operations require the user to be the original comment author.
 * This prevents users from modifying or deleting other users' comments,
 * maintaining conversation integrity and accountability.
 *
 * @see UserModel The authenticated user model
 * @see TaskCommentEntity The comment domain entity being authorized
 */
class TaskCommentPolicy
{
    /**
     * Determine if the user can update the comment.
     *
     * Users can only update comments they authored.
     * This ensures comment integrity and prevents unauthorized edits.
     *
     * Note: The service layer additionally enforces a 30-minute edit window.
     *
     * @param  UserModel  $user  The authenticated user attempting to update the comment
     * @param  TaskCommentEntity  $comment  The comment entity being modified
     * @return bool True if user is the comment author, false otherwise
     *
     * @see \Modules\Workspace\Application\Services\WorkspaceService::updateComment()
     *      For additional 30-minute edit window enforcement
     */
    public function update(UserModel $user, TaskCommentEntity $comment): bool
    {
        // Verify user is the original comment author
        return $comment->getUserId() === $user->id;
    }

    /**
     * Determine if the user can delete the comment.
     *
     * Users can only delete comments they authored.
     * This prevents users from removing other users' contributions
     * to task discussions.
     *
     * @param  UserModel  $user  The authenticated user attempting to delete the comment
     * @param  TaskCommentEntity  $comment  The comment entity being deleted
     * @return bool True if user is the comment author, false otherwise
     */
    public function delete(UserModel $user, TaskCommentEntity $comment): bool
    {
        // Verify user is the original comment author
        return $comment->getUserId() === $user->id;
    }
}
