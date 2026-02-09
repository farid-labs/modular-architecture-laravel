<?php

namespace Modules\Users\Infrastructure\Listeners;

use Modules\Users\Domain\Events\UserCreated;
use Modules\Users\Infrastructure\Jobs\SendWelcomeEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWelcomeEmailListener implements ShouldQueue
{
    public function handle(UserCreated $event): void
    {
        SendWelcomeEmail::dispatch($event->user)
            ->onQueue('emails');
    }
}