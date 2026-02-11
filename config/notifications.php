<?php

use Illuminate\Notifications\DatabaseNotification;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Notification Channel
    |--------------------------------------------------------------------------
    */

    'default_channel' => env('NOTIFICATIONS_DEFAULT', 'mail'),

    /*
    |--------------------------------------------------------------------------
    | Enabled Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [
        'mail' => env('NOTIFICATIONS_MAIL', true),
        'database' => env('NOTIFICATIONS_DATABASE', true),
        'broadcast' => env('NOTIFICATIONS_BROADCAST', false),
        'slack' => env('NOTIFICATIONS_SLACK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'enabled' => env('NOTIFICATIONS_QUEUE', true),
        'connection' => env('NOTIFICATIONS_QUEUE_CONNECTION', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Notification Model
    |--------------------------------------------------------------------------
    */

    'database' => [
        'model' => DatabaseNotification::class,
    ],

];
