<?php

namespace Modules\Workspace\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use Modules\Workspace\Infrastructure\Repositories\WorkspaceRepository;

class WorkspaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the WorkspaceRepositoryInterface to the concrete WorkspaceRepository
        $this->app->bind(
            WorkspaceRepositoryInterface::class,
            fn ($app) => new WorkspaceRepository(WorkspaceModel::class)
        );
    }

    public function boot(): void
    {
        // Load the module's API routes
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/Routes/api.php');

        // Load the module's database migrations
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Database/Migrations');
    }
}
