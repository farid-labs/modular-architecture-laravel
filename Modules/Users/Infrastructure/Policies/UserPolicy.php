<?php

namespace Modules\Users\Infrastructure\Policies;

use Modules\Users\Domain\Entities\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $authenticatedUser, User $targetUser): bool
    {
        return $authenticatedUser->id === $targetUser->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $authenticatedUser, User $targetUser): bool
    {
        return $authenticatedUser->id === $targetUser->id;
    }

    public function delete(User $authenticatedUser, User $targetUser): bool
    {
        return $authenticatedUser->id === $targetUser->id;
    }

    public function restore(User $authenticatedUser, User $targetUser): bool
    {
        return $authenticatedUser->id === $targetUser->id;
    }

    public function forceDelete(User $authenticatedUser, User $targetUser): bool
    {
        return $authenticatedUser->id === $targetUser->id;
    }
}