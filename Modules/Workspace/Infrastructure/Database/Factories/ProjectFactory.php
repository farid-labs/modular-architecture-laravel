<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;

/**
 * @extends Factory<ProjectModel>
 */
class ProjectFactory extends Factory
{
    /** @var class-string<ProjectModel> */
    protected $model = ProjectModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'workspace_id' => WorkspaceModel::factory(),
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
