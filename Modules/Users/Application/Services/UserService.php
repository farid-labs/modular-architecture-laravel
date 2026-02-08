<?php

namespace Modules\Users\Application\Services;

use Modules\Users\Domain\Repositories\UserRepositoryInterface;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Domain\ValueObjects\Name;
use Modules\Users\Domain\Exceptions\UserNotFoundException;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function createUser(UserDTO $userDTO): User
    {
        // Business logic validation
        $this->ensureEmailIsUnique($userDTO->email);
        
        return $this->userRepository->create($userDTO);
    }

    public function updateUser(int $id, UserDTO $userDTO): User
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            throw new UserNotFoundException("User with ID {$id} not found");
        }

        // Business logic validation
        if ($userDTO->email !== $user->email) {
            $this->ensureEmailIsUnique($userDTO->email);
        }

        return $this->userRepository->update($id, $userDTO);
    }

    public function getUserById(int $id): User
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            throw new UserNotFoundException("User with ID {$id} not found");
        }

        return $user;
    }

    public function deleteUser(int $id): bool
    {
        return $this->userRepository->delete($id);
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->getAll();
    }

    private function ensureEmailIsUnique(string $email): void
    {
        if ($this->userRepository->findByEmail($email)) {
            throw new \InvalidArgumentException("Email already exists");
        }
    }
}