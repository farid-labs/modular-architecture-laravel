<?php

namespace Modules\Users\Infrastructure\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Notifications\Infrastructure\Listeners\SendWelcomeNotificationOnUserCreated;
use Modules\Users\Domain\Events\UserCreated;

/**
 * EventServiceProvider for the Users module.
 *
 * Registers domain events and their corresponding listeners.
 * This ensures that when a user is created, appropriate side-effects
 * (e.g., sending welcome notifications) are triggered automatically.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the Users module.
     *
     * Maps domain events to one or more listener classes.
     * Here, when a UserCreated event is dispatched, the
     * SendWelcomeNotificationOnUserCreated will handle sending a welcome notification/email.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserCreated::class => [
            SendWelcomeNotificationOnUserCreated::class,
        ],
    ];

    /**
     * Boot the event service provider.
     *
     * Calls the parent boot method to ensure Laravel's event system
     * is properly initialized. Additional module-specific event
     * registration can be added here if needed.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
