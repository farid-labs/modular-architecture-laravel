<?php

namespace Modules\Users\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Users\Domain\Events\UserCreated;
use Modules\Users\Infrastructure\Jobs\SendWelcomeEmail;

class SendWelcomeEmailListener implements ShouldQueue
{
    public function handle(UserCreated $event): void
    {
        SendWelcomeEmail::dispatch($event->user)
            ->onQueue('emails');
    }
}
