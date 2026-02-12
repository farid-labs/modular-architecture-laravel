<?php

namespace Modules\Users\Infrastructure\Policies;

use Modules\Users\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Log;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        Log::debug('Policy viewAny', ['user_id' => $user->id]);
        return true;
    }

    public function view(User $user, User $model): bool
    {
        $result = $user->id === $model->id || $user->is_admin;
        Log::debug('Policy view', [
            'authenticated_user' => $user->id,
            'target_user' => $model->id,
            'is_same' => $user->id === $model->id,
            'is_admin' => $user->is_admin,
            'result' => $result
        ]);
        return $result;
    }

    public function create(User $user): bool
    {
        Log::debug('Policy create', ['user_id' => $user->id]);
        return true;
    }

    public function update(User $user, User $model): bool
    {
        $result = $user->id === $model->id || $user->is_admin;
        Log::debug('Policy update', [
            'authenticated_user' => $user->id,
            'target_user' => $model->id,
            'is_same' => $user->id === $model->id,
            'is_admin' => $user->is_admin,
            'result' => $result
        ]);
        return $result;
    }

    public function delete(User $user, User $model): bool
    {
        $result = $user->id === $model->id || $user->is_admin;
        Log::debug('Policy delete', [
            'authenticated_user' => $user->id,
            'target_user' => $model->id,
            'is_same' => $user->id === $model->id,
            'is_admin' => $user->is_admin,
            'result' => $result
        ]);
        return $result;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->is_admin;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->is_admin;
    }
}
