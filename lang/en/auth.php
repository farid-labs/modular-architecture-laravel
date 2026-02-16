<?php

declare(strict_types=1);

return [
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    // Custom API messages
    'register_success' => 'User registered successfully',
    'login_success' => 'Login successful',
    'logout_success' => 'Logged out successfully',
    'me_retrieved' => 'User profile retrieved successfully',
    'invalid_credentials' => 'Invalid credentials',
    'unauthorized' => 'Unauthorized access',
    'token_created' => 'Authentication token created',
    'email_exists' => 'Email address is already registered',
    'authentication_failed' => 'Authentication failed',
    'unauthenticated' => 'Unauthenticated',
    'hint_unauthenticated' => 'Please provide a valid authentication token in the Authorization header',

    // Hints for better DX
    'hint_check_credentials' => 'Check your email and password',
    'hint_session_expired' => 'Your session may have expired. Please login again',
    'hint_provide_valid_token' => 'Provide a valid authentication token',
];
