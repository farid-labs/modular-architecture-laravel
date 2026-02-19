<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;

/**
 * Factory class for creating TaskModel instances for testing or seeding.
 *
 * @extends Factory<TaskModel>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TaskModel>
     */
    protected $model = TaskModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Random status and priority
        $status = $this->faker->randomElement(TaskStatus::cases());
        $priority = $this->faker->randomElement(TaskPriority::cases());

        return [
            // Task title and optional description
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),

            // Associate task with a project
            'project_id' => ProjectModel::factory(),

            // Optionally assign to a random existing user
            'assigned_to' => $this->faker->optional()->randomElement(UserModel::pluck('id')->toArray()),

            // Task status and priority
            'status' => $status,
            'priority' => $priority,

            // Optional due date within the next month
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }

    /**
     * Indicate that the task is pending.
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::PENDING,
        ]);
    }

    /**
     * Indicate that the task is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Indicate that the task is completed.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::COMPLETED,
        ]);
    }

    /**
     * Set high priority for the task.
     */
    public function highPriority(): static
    {
        return $this->state(fn () => [
            'priority' => TaskPriority::HIGH,
        ]);
    }

    /**
     * Set low priority for the task.
     */
    public function lowPriority(): static
    {
        return $this->state(fn () => [
            'priority' => TaskPriority::LOW,
        ]);
    }
}
