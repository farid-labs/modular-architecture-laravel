<?php

namespace Modules\Users\Infrastructure\Repositories;

use Illuminate\Support\Facades\Hash;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Domain\Entities\UserEntity;
use Modules\Users\Domain\Repositories\UserRepositoryInterface;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Domain\ValueObjects\Name;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

class UserRepository implements UserRepositoryInterface
{
    private UserModel $model;

    public function __construct(UserModel $model)
    {
        $this->model = $model;
    }

    public function findById(int $id): UserEntity
    {
        $model = $this->model->findOrFail($id);

        return $this->mapToEntity($model);
    }

    public function findByEmail(string $email): ?UserEntity
    {
        $model = $this->model->where('email', $email)->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function create(UserDTO $userDTO): UserEntity
    {
        $data = $userDTO->toArray();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $model = $this->model->create($data);

        return $this->mapToEntity($model);
    }

    public function update(int $id, UserDTO $userDTO): UserEntity
    {
        $model = $this->model->findOrFail($id);

        $data = $userDTO->toArray();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $model->update($data);

        return $this->mapToEntity($model);
    }

    public function delete(int $id): bool
    {
        $model = $this->model->find($id);

        return $model ? (bool) $model->delete() : false;
    }

    /**
     * @return array<int, UserEntity>
     */
    public function getAll(): array
    {
        $models = $this->model->all();

        return $models->map(fn ($model) => $this->mapToEntity($model))->toArray();
    }

    private function mapToEntity(UserModel $model): UserEntity
    {
        return new UserEntity(
            $model->id,
            new Name($model->name),
            new Email($model->email),
            $model->password,
            $model->is_admin,
            $model->email_verified_at,
            $model->created_at,
            $model->updated_at
        );
    }
}
