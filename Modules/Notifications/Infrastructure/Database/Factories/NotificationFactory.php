<?php

namespace Modules\Notifications\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notifications\Infrastructure\Persistence\Models\NotificationModel;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

/**
 * @extends Factory<NotificationModel>
 */
class NotificationFactory extends Factory
{
    protected $model = NotificationModel::class;

    public function definition(): array
    {
        return [
            // FIX: Generate proper UUID format for PostgreSQL
            'id' => $this->generateUuid(),
            'type' => $this->faker->randomElement(['info', 'success', 'warning', 'error']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'category' => $this->faker->randomElement(['system', 'user', 'workspace', 'project', 'task', 'security']),
            'notifiable_type' => UserModel::class,
            'notifiable_id' => UserModel::factory(),
            'data' => [
                'title' => $this->faker->sentence(),
                'message' => $this->faker->paragraph(),
                'action_url' => $this->faker->optional()->url(),
            ],
            'read_at' => null,
            'locale' => 'en',
            'channels' => ['database'],
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function forUser(UserModel $user): static
    {
        return $this->state(fn (array $attributes) => [
            'notifiable_id' => $user->id,
            'notifiable_type' => UserModel::class,
        ]);
    }

    /**
     * Generate proper UUID format for PostgreSQL.
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }
}
