<?php

namespace Modules\Users\Infrastructure\Policies;

use Illuminate\Support\Facades\Log;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

class UserPolicy
{
    public function viewAny(UserModel $user): bool
    {
        Log::debug('Policy viewAny', ['user_id' => $user->id]);

        return true;
    }

    public function view(UserModel $user, UserModel $model): bool
    {
        $result = $user->id === $model->id || $user->getIsAdminAttribute();
        Log::debug('Policy view', [
            'authenticated_user' => $user->id,
            'target_user' => $model->id,
            'is_same' => $user->id === $model->id,
            'is_admin' => $user->getIsAdminAttribute(),
            'result' => $result,
        ]);

        return $result;
    }

    public function create(UserModel $user): bool
    {
        Log::debug('Policy create', ['user_id' => $user->id]);

        return true;
    }

    public function update(UserModel $user, UserModel $model): bool
    {
        $result = $user->id === $model->id || $user->getIsAdminAttribute();
        Log::debug('Policy update', [
            'authenticated_user' => $user->id,
            'target_user' => $model->id,
            'result' => $result,
        ]);

        return $result;
    }

    public function delete(UserModel $user, UserModel $model): bool
    {
        $result = $user->id === $model->id || $user->getIsAdminAttribute();
        Log::debug('Policy delete', [
            'authenticated_user' => $user->id,
            'target_user' => $model->id,
            'result' => $result,
        ]);

        return $result;
    }
}
