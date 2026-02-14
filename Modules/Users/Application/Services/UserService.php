<?php

namespace Modules\Users\Application\Services;

use Illuminate\Support\Facades\Log;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Domain\Entities\UserEntity;
use Modules\Users\Domain\Exceptions\UserNotFoundException;
use Modules\Users\Domain\Repositories\UserRepositoryInterface;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function createUser(UserDTO $userDTO): UserEntity
    {
        $email = $userDTO->email;
        if ($email === null) {
            throw new \InvalidArgumentException('Email is required for user creation');
        }

        Log::channel('domain')->info('Creating user', [
            'email' => $email,
            'name' => $userDTO->name,
        ]);

        $this->ensureEmailIsUnique($email);

        $entity = $this->userRepository->create($userDTO);

        Log::channel('domain')->info('User created successfully', [
            'user_id' => $entity->getId(),
            'email' => $entity->getEmail()->getValue(),
        ]);

        return $entity;
    }

    public function updateUser(int $id, UserDTO $userDTO): UserEntity
    {
        Log::channel('domain')->info('Updating user', [
            'user_id' => $id,
            'email' => $userDTO->email ?? 'not provided',
        ]);

        $entity = $this->userRepository->findById($id);
        if ($entity === null) {
            throw UserNotFoundException::withId($id);
        }

        if ($userDTO->email !== null && $userDTO->email !== $entity->getEmail()->getValue()) {
            $this->ensureEmailIsUnique($userDTO->email);
        }

        $updatedEntity = $this->userRepository->update($id, $userDTO);

        Log::channel('domain')->info('User updated successfully', [
            'user_id' => $id,
            'email' => $updatedEntity->getEmail()->getValue(),
        ]);

        return $updatedEntity;
    }

    public function getUserById(int $id): UserEntity
    {
        Log::channel('domain')->debug('Fetching user by ID', ['user_id' => $id]);

        $entity = $this->userRepository->findById($id);
        if ($entity === null) {
            Log::channel('domain')->warning('User not found', ['user_id' => $id]);
            throw UserNotFoundException::withId($id);
        }

        Log::channel('domain')->debug('User fetched successfully', [
            'user_id' => $id,
            'email' => $entity->getEmail()->getValue(),
        ]);

        return $entity;
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
     * @return array<int, UserEntity>
     */
    public function getAllUsers(): array
    {
        Log::channel('domain')->debug('Fetching all users');

        $entities = $this->userRepository->getAll();

        Log::channel('domain')->debug('Users fetched successfully', ['count' => count($entities)]);

        return $entities;
    }

    private function ensureEmailIsUnique(string $email): void
    {
        $existingEntity = $this->userRepository->findByEmail($email);
        if ($existingEntity !== null) {
            Log::channel('domain')->warning('Email already exists', ['email' => $email]);
            throw new \InvalidArgumentException('Email already exists');
        }
    }
}
