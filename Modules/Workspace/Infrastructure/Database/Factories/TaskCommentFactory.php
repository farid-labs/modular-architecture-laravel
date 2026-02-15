<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskCommentModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;

/**
 * @extends Factory<TaskCommentModel>
 */
class TaskCommentFactory extends Factory
{
    /**
     * @var class-string<TaskCommentModel>
     */
    protected $model = TaskCommentModel::class;

    public function definition(): array
    {
        return [
            'task_id' => TaskModel::factory(),
            'user_id' => UserModel::factory(),
            'comment' => $this->faker->paragraph(1),
        ];
    }

    public function forUser(UserModel $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function forTask(TaskModel $task): static
    {
        return $this->state(fn () => ['task_id' => $task->id]);
    }
}
