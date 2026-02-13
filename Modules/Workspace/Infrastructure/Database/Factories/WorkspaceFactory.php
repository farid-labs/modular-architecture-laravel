<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Users\Infrastructure\Persistence\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Infrastructure\Persistence\Models\Workspace;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Workspace\Infrastructure\Persistence\Models\Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Workspace>
     */
    protected $model = Workspace::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->randomElement(WorkspaceStatus::cases()),
            'owner_id' => User::factory(),
        ];
    }

    /**
     * Indicate the workspace is active.
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => WorkspaceStatus::ACTIVE,
        ]);
    }

    /**
     * Indicate the workspace is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => WorkspaceStatus::INACTIVE,
        ]);
    }

    public function forOwner(User $user): static
    {
        return $this->state(fn () => [
            'owner_id' => $user->id,
        ]);
    }
}
