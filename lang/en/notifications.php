<?php

declare(strict_types=1);

return [

    'priority' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    'category' => [
        'system' => 'System',
        'user' => 'User',
        'workspace' => 'Workspace',
        'project' => 'Project',
        'task' => 'Task',
        'security' => 'Security',
    ],

    'errors' => [
        'user_not_found' => 'User with ID :id not found.',
        'title_too_long' => 'The notification title may not exceed :max characters.',
        'body_too_long' => 'The notification body may not exceed :max characters.',
        'invalid_url' => 'The provided action URL format is invalid.',
    ],

    'validation' => [
        'title_max' => 'The notification title may not exceed :max characters.',
        'body_max' => 'The notification body may not exceed :max characters.',
        'invalid_action_url' => 'The provided action URL format is invalid.',
        'recipient_required' => 'The recipient user is required.',
        'recipient_not_found' => 'The specified recipient user does not exist.',
        'type_required' => 'The notification type is required.',
        'type_invalid' => 'The specified notification type is invalid.',
        'title_required' => 'The notification title is required.',
        'message_required' => 'The notification message is required.',
        'message_max' => 'The notification message may not exceed :max characters.',
        'channel_invalid' => 'The specified notification channel is invalid.',

    ],

    'created' => 'Notification has been created successfully.',
    'retrieved' => 'Notifications retrieved successfully.',
    'deleted' => 'Notification has been deleted successfully.',
    'marked_read' => 'Notification marked as read.',
    'all_marked_read' => 'All notifications marked as read.',
    'unread_count_retrieved' => 'Unread notification count retrieved successfully.',
    'not_found' => 'Notification not found.',
    'not_found_or_unauthorized' => 'Notification not found or you do not have permission to access it',
    'invalid_id_format' => 'Invalid notification ID format',
    // Test-specific translations
    'test_title' => 'Test Notification',
    'test_message' => 'This is a test notification message.',
    'view_action' => 'View',
    'job_test_title' => 'Job Test Notification',
    'job_test_message' => 'This notification was sent via queued job.',
    'default_action_label' => 'Take Action',

    'greetings' => [
        'success' => 'Success!',
        'warning' => 'Warning!',
        'error' => 'Error!',
        'default' => 'Hello!',
    ],

];
