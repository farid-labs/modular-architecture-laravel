<?php

namespace Modules\Users\Infrastructure\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Users\Domain\Events\UserCreated;
use Modules\Users\Infrastructure\Listeners\SendWelcomeEmailListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserCreated::class => [
            SendWelcomeEmailListener::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
