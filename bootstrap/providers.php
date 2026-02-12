<?php

return [
    Modules\Notifications\Infrastructure\Providers\NotificationsServiceProvider::class,
    Modules\Users\Infrastructure\Providers\EventServiceProvider::class,
    Modules\Users\Infrastructure\Providers\RepositoryServiceProvider::class,
    Modules\Users\Infrastructure\Providers\UsersServiceProvider::class,
    Modules\Workspace\Infrastructure\Providers\WorkspaceServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
];
