<?php

namespace Modules\Users\Infrastructure\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Users\Infrastructure\Policies\UserPolicy;

class UsersServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(RepositoryServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Database/Migrations');
        Gate::policy(UserModel::class, UserPolicy::class);
    }
}
