<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Persistence\Models\Task;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskComment;

/**
 * @extends Factory<TaskComment>
 */
class TaskCommentFactory extends Factory
{
    /**
     * @var class-string<TaskComment>
     */
    protected $model = TaskComment::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => UserModel::factory(),
            'comment' => $this->faker->paragraph(1),
        ];
    }

    public function forUser(UserModel $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function forTask(Task $task): static
    {
        return $this->state(fn () => ['task_id' => $task->id]);
    }
}
