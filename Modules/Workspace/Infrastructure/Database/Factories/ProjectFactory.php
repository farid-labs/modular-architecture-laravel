<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Modules\Workspace\Infrastructure\Persistence\Models\Project;
use Modules\Workspace\Infrastructure\Persistence\Models\Workspace;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /** @var class-string<Project> */
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'workspace_id' => Workspace::factory(),
            'status' => $this->faker->randomElement(ProjectStatus::cases()),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::ACTIVE,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::ARCHIVED,
        ]);
    }
}
