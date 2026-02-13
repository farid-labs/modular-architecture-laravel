<?php

namespace Modules\Users\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Users\Domain\Repositories\UserRepositoryInterface;
use Modules\Users\Infrastructure\Persistence\Models\User;
use Modules\Users\Infrastructure\Repositories\UserRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            fn ($app) => new UserRepository(new User)
        );
    }
}
