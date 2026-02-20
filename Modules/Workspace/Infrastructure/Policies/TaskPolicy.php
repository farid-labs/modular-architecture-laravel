<?php

namespace Modules\Workspace\Infrastructure\Policies;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Entities\TaskEntity;

class TaskPolicy
{
    public function view(UserModel $user, TaskEntity $task): bool
    {
        return $this->isMemberOfProject($user, $task);
    }

    public function update(UserModel $user, TaskEntity $task): bool
    {
        return $this->isMemberOfProject($user, $task);
    }

    public function complete(UserModel $user, TaskEntity $task): bool
    {
        return $this->isMemberOfProject($user, $task);
    }

    private function isMemberOfProject(UserModel $user, TaskEntity $task): bool
    {
        return true;
    }

    public function comment(UserModel $user, TaskEntity $task): bool
    {
        return $this->isMemberOfProject($user, $task);
    }
}
