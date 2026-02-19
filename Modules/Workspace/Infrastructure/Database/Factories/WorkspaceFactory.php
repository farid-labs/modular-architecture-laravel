<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;

/**
 * Factory class for creating WorkspaceModel instances for testing or seeding.
 *
 * @extends Factory<WorkspaceModel>
 */
class WorkspaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WorkspaceModel>
     */
    protected $model = WorkspaceModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            // Workspace name and slug
            'name' => $name,
            'slug' => Str::slug($name),

            // Optional description
            'description' => $this->faker->optional()->paragraph(),

            // Random status
            'status' => $this->faker->randomElement(WorkspaceStatus::cases()),

            // Owner of the workspace
            'owner_id' => UserModel::factory(),
        ];
    }

    /**
     * Indicate that the workspace is active.
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => WorkspaceStatus::ACTIVE,
        ]);
    }

    /**
     * Indicate that the workspace is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => WorkspaceStatus::INACTIVE,
        ]);
    }

    /**
     * Set the workspace owner.
     */
    public function forOwner(UserModel $user): static
    {
        return $this->state(fn () => [
            'owner_id' => $user->id,
        ]);
    }
}
