<?php

namespace Modules\Users\Domain\Repositories;

use Modules\Users\Infrastructure\Persistence\Models\User;
use Modules\Users\Application\DTOs\UserDTO;

interface UserRepositoryInterface
{

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(UserDTO $userDTO): User;

    public function update(int $id, UserDTO $userDTO): ?User;

    public function delete(int $id): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array;
}
