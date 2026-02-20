<?php

namespace Modules\Workspace\Infrastructure\Policies;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;

class TaskAttachmentPolicy
{
    public function view(UserModel $user, TaskAttachmentEntity $attachment): bool
    {
        return true;
    }

    public function delete(UserModel $user, TaskAttachmentEntity $attachment): bool
    {
        return $attachment->getUserId() === $user->id;
    }
}
