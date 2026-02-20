<?php

namespace Modules\Workspace\Infrastructure\Policies;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Entities\TaskCommentEntity;

class TaskCommentPolicy
{
    public function update(UserModel $user, TaskCommentEntity $comment): bool
    {
        return $comment->getUserId() === $user->id;
    }

    public function delete(UserModel $user, TaskCommentEntity $comment): bool
    {
        return $comment->getUserId() === $user->id;
    }
}
