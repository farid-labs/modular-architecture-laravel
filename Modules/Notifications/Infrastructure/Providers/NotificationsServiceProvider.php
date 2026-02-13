<?php

namespace Modules\Notifications\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class NotificationsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Database/Migrations');
    }
}
