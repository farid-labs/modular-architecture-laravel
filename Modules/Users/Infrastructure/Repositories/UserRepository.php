<?php

namespace Modules\Users\Infrastructure\Repositories;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        $data['password'] = Hash::make($data['password']);

        $model = $this->model->create($data);

        return new UserEntity(
            $model->id,
            new Name($model->name),
            new Email($model->email),
            $model->email_verified_at,
            $model->created_at,
            $model->updated_at,
            $model->is_admin
        );
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
        $entities = [];

        foreach ($models as $model) {
            try {
                $entities[] = $this->mapToEntity($model);
            } catch (\InvalidArgumentException $e) {
                Log::warning('Skipped invalid user record during mapping', [
                    'user_id' => $model->id ?? 'unknown',
                    'email' => $model->email ?? 'empty',
                    'name' => $model->name ?? 'unknown',
                    'error' => $e->getMessage(),
                    'invalid_data_sample' => substr($model->email ?? '', 0, 50),
                ]);

                continue;
            } catch (\Throwable $e) {
                Log::error('Unexpected error mapping user record', [
                    'user_id' => $model->id ?? 'unknown',
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                continue;
            }
        }

        return $entities;
    }

    private function mapToEntity(UserModel $model): UserEntity
    {
        return new UserEntity(
            $model->id,
            new Name($model->name),
            new Email($model->email),
            $model->email_verified_at,
            $model->created_at,
            $model->updated_at,
            $model->is_admin
        );
    }
}
