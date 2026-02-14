<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;
use Modules\Workspace\Infrastructure\Persistence\Models\Project;
use Modules\Workspace\Infrastructure\Persistence\Models\Task;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Workspace\Infrastructure\Persistence\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Modules\Workspace\Infrastructure\Persistence\Models\Task>
     */
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(TaskStatus::cases());
        $priority = $this->faker->randomElement(TaskPriority::cases());

        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'project_id' => Project::factory(),
            'assigned_to' => $this->faker->optional()->randomElement(UserModel::pluck('id')->toArray()),
            'status' => $status,
            'priority' => $priority,
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
