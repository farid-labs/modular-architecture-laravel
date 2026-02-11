<?php

namespace Modules\Users\Infrastructure\Policies;

use Modules\Users\Infrastructure\Persistence\Models\User;

/**
 * @template TFactory of \Illuminate\Database\Eloquent\Factories\Factory
 */
class UserPolicy
{
    /**
     * @param User $user
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $authenticatedUser, User $targetUser): bool
    {
        return $authenticatedUser->id === $targetUser->id;
    }

    /**
     * @param User $user
     */
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
