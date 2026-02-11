<?php

namespace Modules\Users\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class UsersServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(\Modules\Users\Infrastructure\Providers\RepositoryServiceProvider::class);
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
