<?php

declare(strict_types=1);

return [
    // Success Messages
    'created' => 'Workspace created successfully',
    'updated' => 'Workspace updated successfully',
    'deleted' => 'Workspace deleted successfully',
    'retrieved' => 'Workspaces retrieved successfully',
    'member_added' => 'Member added successfully',
    'member_removed' => 'Member removed successfully',

    // Error Messages
    'not_found' => 'Workspace with slug \':slug\' not found',
    'not_found_by_id' => 'Workspace with ID \':id\' not found',
    'invalid_parameter' => 'Invalid parameter. This endpoint requires a workspace slug (e.g., "marketing-team"), not an ID.',
    'hint_use_list' => 'Use GET /api/v1/workspaces to list available workspaces.',

    // Validation
    'slug_required' => 'The workspace slug is required.',
    'slug_invalid_format' => 'The slug must contain only lowercase letters, numbers, and hyphens.',
    'slug_taken' => 'This slug is already taken.',
    'name_min' => 'Workspace name must be at least 3 characters',
    'name_max' => 'Workspace name must not exceed 100 characters',
    'description_max' => 'Description must not exceed 1000 characters',
    'invalid_status' => 'Invalid workspace status',

    // Permissions
    'not_member' => 'User is not a member of this workspace',
    'not_owner' => 'Only the workspace owner can perform this action',
    'invalid_role' => 'Invalid role \':role\'. Must be one of: owner, admin, member',
    'no_fields_to_update' => 'No valid fields provided for update',
    'invalid_id_format' => 'Invalid ID format. ID must be a positive integer.',
    'hint_valid_id' => 'Example: /api/v1/workspaces/27',

    'project_not_found' => 'Project with ID :id not found',
    'not_member_of_project' => 'You do not have permission to create tasks in this project.',
    'not_member_project' => 'User is not a member of this Project',
    'date_cannot_past' => 'Due date cannot be in the past',
    // Task messages
    'task_not_found' => 'Task with ID :id not found',
    'task_created' => 'Task created successfully',
    'task_retrieved' => 'Task retrieved successfully',
    'task_completed' => 'Task completed successfully',
    'task_update_fail' => 'Task :taskId could not be updated to completed status',

    'projects_retrieved' => 'Projects retrieved successfully',
    'project_created' => 'Project created successfully',
    'project_retrieved' => 'Project retrieved successfully',
    'workspace_not_found' => 'Workspace with ID :id not found',
    'not_member_of_workspace' => 'You do not have permission to access this workspace',
    'workspaces_retrieved' => 'Workspaces retrieved successfully',
    'workspace_created' => 'Workspace created successfully',

    'membership_not_found' => 'User :user_id is not a member of workspace :workspace_id',
    'comment_min_length' => 'Comment must be at least 3 characters',
    'invalid_file_type' => 'Invalid file type. Allowed types: JPEG, PNG, PDF',
    'file_size_exceeds_limit' => 'File size exceeds maximum limit of 10MB',

];
