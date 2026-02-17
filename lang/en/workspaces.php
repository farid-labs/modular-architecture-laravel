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
];
