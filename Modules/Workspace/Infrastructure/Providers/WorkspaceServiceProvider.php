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
        $this->app->bind(
            WorkspaceRepositoryInterface::class,
            fn ($app) => new WorkspaceRepository(new WorkspaceModel)
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Database/Migrations');
    }
}
