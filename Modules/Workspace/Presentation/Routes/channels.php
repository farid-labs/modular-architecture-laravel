<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('task.{taskId}', function ($user, $taskId) {
    return $user !== null;
});
