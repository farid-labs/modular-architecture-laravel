<?php

declare(strict_types=1);

return [
    // General API Errors
    'validation_failed' => 'Validation failed',
    'server_error' => 'Internal server error',

    // HTTP Method Errors
    'method_not_allowed' => 'Method not allowed for this endpoint',
    'endpoint_not_found' => 'Endpoint not found',
    'hint_method' => 'Please use one of the following methods: :methods',
    'hint_check_url' => 'Check the URL path and HTTP method',

    // Authorization Errors
    'unauthenticated' => 'Unauthenticated',
    'forbidden' => 'Forbidden',

    // Rate Limiting
    'too_many_requests' => 'Too many requests. Please try again later.',

    // Resource Errors
    'resource_not_found' => ':resource not found',

    // Authorization Hints
    'hint_check_permissions' => 'Check your authentication token and permissions',
    'hint_insufficient_permissions' => 'You do not have permission to access this resource',
    'hint_cannot_update_others' => 'You can only update your own user profile',
    'hint_cannot_delete_others' => 'You can only delete your own user account',
];
