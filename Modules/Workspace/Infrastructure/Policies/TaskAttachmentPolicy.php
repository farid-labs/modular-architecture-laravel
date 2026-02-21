<?php

namespace Modules\Workspace\Infrastructure\Policies;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;

/**
 * Authorization policy for Task Attachment entities.
 *
 * Defines access control rules for task attachment operations.
 * Implements a hybrid authorization strategy:
 * - View: Open to all authenticated users (team transparency)
 * - Delete: Restricted to file uploader only (ownership control)
 *
 * Policy Methods:
 * - view: Check if user can view/download attachment
 * - delete: Check if user can remove attachment
 *
 * Authorization Strategy:
 * Viewing attachments is open to promote team collaboration and transparency.
 * Deleting attachments is restricted to the uploader to prevent accidental
 * or malicious removal of team members' files.
 *
 * @see UserModel The authenticated user model
 * @see TaskAttachmentEntity The attachment domain entity being authorized
 */
class TaskAttachmentPolicy
{
    /**
     * Determine if the user can view the attachment.
     *
     * All authenticated users can view attachments.
     * This promotes team transparency and collaboration by allowing
     * all team members to access project files.
     *
     * @param  UserModel  $user  The authenticated user attempting to view the attachment
     * @param  TaskAttachmentEntity  $attachment  The attachment entity being accessed
     * @return bool Always returns true for authenticated users
     *
     * @note Authorization is handled at the workspace/project level
     *       Users must already have access to the task to reach this point
     */
    public function view(UserModel $user, TaskAttachmentEntity $attachment): bool
    {
        // Allow all authenticated users to view attachments
        // Task-level authorization already verified by controller
        return true;
    }

    /**
     * Determine if the user can delete the attachment.
     *
     * Users can only delete attachments they uploaded.
     * This prevents users from removing other team members' files
     * and maintains accountability for uploaded content.
     *
     * @param  UserModel  $user  The authenticated user attempting to delete the attachment
     * @param  TaskAttachmentEntity  $attachment  The attachment entity being deleted
     * @return bool True if user is the attachment uploader, false otherwise
     */
    public function delete(UserModel $user, TaskAttachmentEntity $attachment): bool
    {
        // Verify user is the original file uploader
        return $attachment->getUserId() === $user->id;
    }
}
