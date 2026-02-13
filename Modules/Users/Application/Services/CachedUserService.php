<?php

namespace Modules\Users\Application\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Infrastructure\Persistence\Models\User;

class CachedUserService
{
    private const USER_CACHE_KEY = 'user_{id}';

    private const USER_CACHE_TTL = 3600; // 1 hour

    private const USERS_LIST_CACHE_KEY = 'users_list';

    private const USERS_LIST_CACHE_TTL = 1800; // 30 minutes

    public function __construct(
        private UserService $userService
    ) {}

    private function getCacheKey(int $id): string
    {
        return str_replace('{id}', (string) $id, self::USER_CACHE_KEY);
    }

    public function getUserById(int $id): User
    {
        $cacheKey = $this->getCacheKey($id);

        return Cache::remember($cacheKey, self::USER_CACHE_TTL, function () use ($id) {
            Log::info("Cache miss for user {$id}, fetching from database");

            return $this->userService->getUserById($id);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllUsers(): array
    {
        return Cache::remember(self::USERS_LIST_CACHE_KEY, self::USERS_LIST_CACHE_TTL, function () {
            Log::info('Cache miss for users list, fetching from database');

            return $this->userService->getAllUsers();
        });
    }

    public function createUser(UserDTO $userDTO): User
    {
        /** @var User $user */
        $user = $this->userService->createUser($userDTO);

        // Invalidate users list cache
        Cache::forget(self::USERS_LIST_CACHE_KEY);

        Log::info("User {$user->id} created, cache invalidated");

        return $user;
    }

    public function updateUser(int $id, UserDTO $userDTO): User
    {
        /** @var User $user */
        $user = $this->userService->updateUser($id, $userDTO);

        // Invalidate both user and list caches
        $cacheKey = $this->getCacheKey($id);
        Cache::forget($cacheKey);
        Cache::forget(self::USERS_LIST_CACHE_KEY);

        Log::info("User {$id} updated, cache invalidated");

        return $user;
    }

    public function deleteUser(int $id): bool
    {
        $result = $this->userService->deleteUser($id);

        // Invalidate both user and list caches
        $cacheKey = $this->getCacheKey($id);
        Cache::forget($cacheKey);
        Cache::forget(self::USERS_LIST_CACHE_KEY);

        Log::info("User {$id} deleted, cache invalidated");

        return $result;
    }

    public function clearUserCache(int $id): void
    {
        $cacheKey = $this->getCacheKey($id);
        Cache::forget($cacheKey);
        Cache::forget(self::USERS_LIST_CACHE_KEY);

        Log::info("Cache cleared for user {$id}");
    }
}
