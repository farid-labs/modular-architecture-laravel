<?php

namespace Modules\Notifications\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use Modules\Notifications\Infrastructure\Listeners\SendWelcomeNotificationOnUserCreated;
use Modules\Notifications\Infrastructure\Repositories\NotificationRepository;

/**
 * Service Provider for the Notifications module.
 *
 * This provider is responsible for:
 *  - Registering bindings
 *  - Loading routes, migrations and channels
 *  - Registering event listeners
 */
class NotificationsServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \Modules\Users\Domain\Events\UserCreated::class => [
            SendWelcomeNotificationOnUserCreated::class,
        ],
    ];

    /**
     * Register bindings and services for the module.
     */
    public function register(): void
    {
        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationRepository::class
        );

        // Singleton so the listener can be resolved cleanly
        $this->app->singleton(NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load API routes
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/Routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Database/Migrations');

        // Load broadcasting channels
        $channelsPath = __DIR__.'/../../Presentation/Routes/channels.php';
        if (file_exists($channelsPath)) {
            require $channelsPath;
        }

        // $listen is already defined at class level → no need to re-assign here
    }
}
