<?php

namespace Modules\Workspace\Infrastructure\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Events\TaskAttachmentUploaded;
use Modules\Workspace\Domain\Events\TaskCommentAdded;
use Modules\Workspace\Domain\Events\TaskCommentUpdated;
use Modules\Workspace\Domain\Events\TaskCompleted;
use Modules\Workspace\Domain\Events\TaskCreated;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Modules\Workspace\Infrastructure\Listeners\LogAttachmentUploadListener;
use Modules\Workspace\Infrastructure\Listeners\LogTaskActivityListener;
use Modules\Workspace\Infrastructure\Listeners\SendTaskNotificationListener;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use Modules\Workspace\Infrastructure\Policies\TaskAttachmentPolicy;
use Modules\Workspace\Infrastructure\Policies\TaskCommentPolicy;
use Modules\Workspace\Infrastructure\Policies\TaskPolicy;
use Modules\Workspace\Infrastructure\Repositories\WorkspaceRepository;

class WorkspaceServiceProvider extends ServiceProvider
{
    /**
     * Event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        TaskCommentAdded::class => [
            LogTaskActivityListener::class,
        ],
        TaskCommentUpdated::class => [
            LogTaskActivityListener::class,
        ],
        TaskAttachmentUploaded::class => [
            LogTaskActivityListener::class,
            LogAttachmentUploadListener::class,
        ],
        TaskCreated::class => [
            SendTaskNotificationListener::class,
        ],
        TaskCompleted::class => [
            SendTaskNotificationListener::class,
        ],
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            WorkspaceRepositoryInterface::class,
            fn($app) => new WorkspaceRepository(WorkspaceModel::class)
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load module routes
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');

        // Load module migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Database/Migrations');

        // Register Policies
        Gate::policy(TaskEntity::class, TaskPolicy::class);
        Gate::policy(TaskCommentEntity::class, TaskCommentPolicy::class);
        Gate::policy(TaskAttachmentEntity::class, TaskAttachmentPolicy::class);
    }
}
