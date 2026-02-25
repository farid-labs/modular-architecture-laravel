<?php

namespace Modules\Notifications\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for the Notifications module.
 *
 * This provider is responsible for:
 *  - Registering any bindings or services specific to the Notifications module.
 *  - Bootstrapping module routes, migrations, and broadcast channels.
 *  - Ensuring that the module is fully integrated with Laravel's service container.
 */
class NotificationsServiceProvider extends ServiceProvider
{
    /**
     * Register bindings and services for the module.
     *
     * Use this method to bind interfaces to implementations
     * or register services in the container.
     *
     * @return void
     */
    public function register(): void
    {
        // Example:
        // $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
    }

    /**
     * Bootstrap any application services.
     *
     * This method loads:
     *  - API routes for the Notifications module.
     *  - Database migrations for Notifications tables.
     *  - Broadcasting channels for real-time notifications.
     *
     * @return void
     */
    public function boot(): void
    {
        // Load API routes from module
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');

        // Load migrations for this module
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Database/Migrations');

        // Load broadcasting channels if the file exists
        // Ensures private and workspace channels are registered
        $channelsPath = __DIR__ . '/../../Presentation/Routes/channels.php';
        if (file_exists($channelsPath)) {
            require $channelsPath;
        }
    }
}
