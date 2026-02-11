<?php

namespace Modules\Users\Infrastructure\Repositories;

use Modules\Users\Infrastructure\Persistence\Models\User;
use Modules\Users\Domain\Repositories\UserRepositoryInterface;
use Modules\Users\Application\DTOs\UserDTO;
use Illuminate\Support\Facades\Hash;

/**
 * @template TFactory of \Illuminate\Database\Eloquent\Factories\Factory
 */
class UserRepository implements UserRepositoryInterface
{
    private User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }
    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function create(UserDTO $userDTO): User
    {
        $data = $userDTO->toArray();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->model->create($data);
    }

    public function update(int $id, UserDTO $userDTO): ?User
    {
        $user = $this->findById($id);

        if (!$user instanceof User) {
            return null;
        }

        $data = $userDTO->toArray();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return $user;
    }

    public function delete(int $id): bool
    {
        $user = $this->findById($id);

        if (!$user instanceof User) {
            return false;
        }

        return $user->delete() === true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->model->all()->toArray();
    }
}
