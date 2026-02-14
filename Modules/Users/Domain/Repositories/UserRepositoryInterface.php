<?php

namespace Modules\Users\Domain\Repositories;

use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Domain\Entities\UserEntity;

interface UserRepositoryInterface
{
    public function findById(int $id): ?UserEntity;

    public function findByEmail(string $email): ?UserEntity;

    public function create(UserDTO $userDTO): UserEntity;

    public function update(int $id, UserDTO $userDTO): UserEntity;

    public function delete(int $id): bool;

    /**
     * @return array<int, UserEntity>
     */
    public function getAll(): array;
}
