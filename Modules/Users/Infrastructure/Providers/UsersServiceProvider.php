<?php

namespace Modules\Users\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Modules\Users\Infrastructure\Persistence\Models\User;
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
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Database/Migrations');
        Gate::policy(User::class, UserPolicy::class);

        Log::info('UserPolicy registered for User model: ' . (Gate::getPolicyFor(User::class) ? 'YES' : 'NO'));
    }
}
