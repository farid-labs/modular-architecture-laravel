<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;

/**
 * Factory class for creating ProjectModel instances for testing or seeding.
 *
 * @extends Factory<ProjectModel>
 */
class ProjectFactory extends Factory
{
    /** @var class-string<ProjectModel> The model this factory generates */
    protected $model = ProjectModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Random project name
            'name' => $this->faker->sentence(3),

            // Optional project description
            'description' => $this->faker->optional()->paragraph(),

            // Associate project with a workspace
            'workspace_id' => WorkspaceModel::factory(),

            // Random status from enum cases
            'status' => $this->faker->randomElement(ProjectStatus::cases()),
        ];
    }

    /**
     * Set project status to ACTIVE
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::ACTIVE,
        ]);
    }

    /**
     * Set project status to ARCHIVED
     */
    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::ARCHIVED,
        ]);
    }
}
