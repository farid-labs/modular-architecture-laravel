<?php

declare(strict_types=1);

return [
    // ===== Success Messages =====
    'created' => 'Workspace created successfully',
    'updated' => 'Workspace updated successfully',
    'deleted' => 'Workspace deleted successfully',
    'retrieved' => 'Workspace retrieved successfully',
    'member_added' => 'Member added successfully',
    'member_removed' => 'Member removed successfully',
    'members_retrieved' => 'Workspace members retrieved successfully',
    'projects_retrieved' => 'Projects retrieved successfully',
    'project_created' => 'Project created successfully',
    'project_retrieved' => 'Project retrieved successfully',
    'project_updated' => 'Project updated successfully',
    'project_deleted' => 'Project deleted successfully',
    'tasks_retrieved' => 'Tasks retrieved successfully',
    'task_created' => 'Task created successfully',
    'task_retrieved' => 'Task retrieved successfully',
    'task_updated' => 'Task updated successfully',
    'task_deleted' => 'Task deleted successfully',
    'task_completed' => 'Task completed successfully',
    'comments_retrieved' => 'Comments retrieved successfully',
    'comment_added' => 'Comment added successfully',
    'comment_updated' => 'Comment updated successfully',
    'comment_deleted' => 'Comment deleted successfully',
    'attachments_retrieved' => 'Attachments retrieved successfully',
    'attachment_uploaded' => 'Attachment uploaded successfully',
    'attachment_deleted' => 'Attachment deleted successfully',
    'workspaces_retrieved' => 'Workspaces retrieved successfully',
    'workspace_created' => 'Workspace created successfully',

    // ===== Error Messages =====
    'not_found' => 'Workspace with slug \':slug\' not found',
    'not_found_by_id' => 'Workspace with ID \':id\' not found',
    'project_not_found' => 'Project with ID :id not found',
    'task_not_found' => 'Task with ID :id not found',
    'workspace_not_found' => 'Workspace with ID :id not found',
    'membership_not_found' => 'User :user_id is not a member of workspace :workspace_id',
    'invalid_parameter' => 'Invalid parameter. This endpoint requires a workspace slug, not an ID.',
    'hint_use_list' => 'Use GET /api/v1/workspaces to list available workspaces.',
    'invalid_id_format' => 'Invalid ID format. ID must be a positive integer.',
    'hint_valid_id' => 'Example: /api/v1/workspaces/27',

    // ===== Validation Messages =====
    'slug_required' => 'The workspace slug is required.',
    'slug_invalid_format' => 'The slug must contain only lowercase letters, numbers, and hyphens.',
    'slug_taken' => 'This slug is already taken.',
    'name_min' => 'Workspace name must be at least 3 characters',
    'name_max' => 'Workspace name must not exceed 100 characters',
    'description_max' => 'Description must not exceed 1000 characters',
    'invalid_status' => 'Invalid workspace status',
    'no_fields_to_update' => 'No valid fields provided for update',
    'validation_failed' => 'Validation failed',
    'update_failed' => 'Update failed',

    // ===== Permission Messages =====
    'not_member' => 'User is not a member of this workspace',
    'not_member_of_workspace' => 'You do not have permission to access this workspace',
    'not_member_of_project' => 'You do not have permission to create tasks in this project',
    'not_member_project' => 'User is not a member of this project',
    'not_owner' => 'Only the workspace owner can perform this action',
    'invalid_role' => 'Invalid role \':role\'. Must be one of: owner, admin, member',

    // ===== Task Messages =====
    'date_cannot_past' => 'Due date cannot be in the past',
    'task_update_fail' => 'Task :taskId could not be updated to completed status',
    'comment_min_length' => 'Comment must be at least 3 characters',
    'comment_max_length' => 'Comment must not exceed 2000 characters',
    'comment_not_owned' => 'You can only delete your own comments',
    'comment_edit_expired' => 'Comment can only be edited within 30 minutes of creation',
    'invalid_file_type' => 'Invalid file type. Allowed types: JPEG, PNG, PDF',
    'file_size_exceeds_limit' => 'File size exceeds maximum limit of 10MB',
    'invalid_file_name' => 'Invalid file name',
    'invalid_file_path' => 'Invalid file path',
    'attachment_not_owned' => 'You can only delete your own attachments',
    'task_title_min_length' => 'Task title must be at least 3 characters',
    'task_title_max_length' => 'Task title must not exceed 255 characters',

    'workspace_retrieved' => 'Workspace retrieved successfully',

];
