<?php

namespace Modules\Users\Application\Services;

use Modules\Users\Domain\Repositories\UserRepositoryInterface;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Infrastructure\Persistence\Models\User;
use Modules\Users\Domain\Exceptions\UserNotFoundException;
use Illuminate\Support\Facades\Log;


class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}


    public function createUser(UserDTO $userDTO): User
    {
        Log::channel('domain')->info('Creating user', [
            'email' => $userDTO->email,
            'name' => $userDTO->name
        ]);

        // Business logic validation
        $this->ensureEmailIsUnique($userDTO->email);

        $user = $this->userRepository->create($userDTO);

        Log::channel('domain')->info('User created successfully', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return $user;
    }


    public function updateUser(int $id, UserDTO $userDTO): User
    {
        Log::channel('domain')->info('Updating user', [
            'user_id' => $id,
            'email' => $userDTO->email
        ]);

        $user = $this->userRepository->findById($id);

        if (!$user instanceof User) {
            Log::channel('domain')->error('User not found for update', ['user_id' => $id]);
            throw UserNotFoundException::withId($id);
        }

        // Business logic validation
        if ($userDTO->email !== $user->email) {
            $this->ensureEmailIsUnique($userDTO->email);
        }

        $updatedUser = $this->userRepository->update($id, $userDTO);

        if (!$updatedUser instanceof User) {
            throw new \RuntimeException("Failed to update user");
        }

        Log::channel('domain')->info('User updated successfully', [
            'user_id' => $id,
            'email' => $updatedUser->email
        ]);

        return $updatedUser;
    }


    public function getUserById(int $id): User
    {
        Log::channel('domain')->debug('Fetching user by ID', ['user_id' => $id]);

        $user = $this->userRepository->findById($id);

        if (!$user instanceof User) {
            Log::channel('domain')->warning('User not found', ['user_id' => $id]);
            throw UserNotFoundException::withId($id);
        }

        Log::channel('domain')->debug('User fetched successfully', [
            'user_id' => $id,
            'email' => $user->email
        ]);

        return $user;
    }

    public function deleteUser(int $id): bool
    {
        Log::channel('domain')->info('Deleting user', ['user_id' => $id]);

        $result = $this->userRepository->delete($id);

        if ($result) {
            Log::channel('domain')->info('User deleted successfully', ['user_id' => $id]);
        } else {
            Log::channel('domain')->warning('User not found for deletion', ['user_id' => $id]);
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllUsers(): array
    {
        Log::channel('domain')->debug('Fetching all users');

        $users = $this->userRepository->getAll();

        Log::channel('domain')->debug('Users fetched successfully', ['count' => count($users)]);

        return $users;
    }

    private function ensureEmailIsUnique(string $email): void
    {
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser instanceof User) {
            Log::channel('domain')->warning('Email already exists', ['email' => $email]);
            throw new \InvalidArgumentException("Email already exists");
        }
    }
}
