<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskCommentModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;

/**
 * Factory class for creating TaskCommentModel instances for testing or seeding.
 *
 * @extends Factory<TaskCommentModel>
 */
class TaskCommentFactory extends Factory
{
    /**
     * The model this factory generates.
     *
     * @var class-string<TaskCommentModel>
     */
    protected $model = TaskCommentModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Associate comment with a task
            'task_id' => TaskModel::factory(),

            // Associate comment with a user
            'user_id' => UserModel::factory(),

            // Random comment content
            'comment' => $this->faker->paragraph(1),
        ];
    }

    /**
     * Set the comment to belong to a specific user.
     */
    public function forUser(UserModel $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /**
     * Set the comment to belong to a specific task.
     */
    public function forTask(TaskModel $task): static
    {
        return $this->state(fn () => ['task_id' => $task->id]);
    }
}
