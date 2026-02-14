<?php

namespace Modules\Users\Tests\Feature\Presentation\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Users\Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Authenticate a user (helper)
     */
    protected function authenticate(bool $admin = false): UserModel
    {
        $user = UserModel::create([
            'name' => $admin ? 'Admin User' : 'Test User',
            'email' => $admin ? 'admin@test.com' : 'user@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => $admin,
        ]);

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_can_list_users(): void
    {
        $auth = $this->authenticate();
        UserModel::factory()->count(3)->create();

        $this->getJson('/v1/users')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    }

    public function test_can_show_single_user(): void
    {
        $this->authenticate(admin: true); // Admin can view any user

        // Create factory user with explicit, predictable values
        $user = UserModel::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $this->getJson("/v1/users/{$user->id}")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                ],
            ]);
    }

    public function test_can_create_user(): void
    {
        $this->authenticate();

        $data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123',
        ];

        $this->postJson('/v1/users', $data)
            ->assertCreated()
            ->assertJson(['data' => ['email' => $data['email']]]);

        $this->assertDatabaseHas('users', ['email' => $data['email']]);
    }

    public function test_cannot_create_user_with_existing_email(): void
    {
        $this->authenticate();
        $existing = UserModel::factory()->create();

        $this->postJson('/v1/users', [
            'name' => $this->faker->name(),
            'email' => $existing->email,
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_can_update_own_user(): void
    {
        $auth = $this->authenticate();

        $this->putJson("/v1/users/{$auth->id}", [
            'name' => 'My New Name',
            'email' => $this->faker->unique()->safeEmail(),
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $auth->id,
            'name' => 'My New Name',
        ]);
    }

    public function test_cannot_update_other_user_without_permission(): void
    {
        $this->authenticate();
        $other = UserModel::factory()->create();

        $this->putJson("/v1/users/{$other->id}", [
            'name' => 'Hacked',
            'email' => $this->faker->unique()->safeEmail(),
        ])->assertForbidden();
    }

    public function test_can_delete_own_user(): void
    {
        $auth = $this->authenticate();

        $this->deleteJson("/v1/users/{$auth->id}")
            ->assertOk();
    }

    public function test_cannot_delete_other_user_without_permission(): void
    {
        $this->authenticate();
        $other = UserModel::factory()->create();

        $this->deleteJson("/v1/users/{$other->id}")
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $this->getJson('/v1/users')
            ->assertUnauthorized();
    }

    public function test_can_show_own_user(): void
    {
        $auth = $this->authenticate();

        $this->getJson("/v1/users/{$auth->id}")
            ->assertOk();
    }

    public function test_cannot_show_other_user_without_permission(): void
    {
        $this->authenticate();
        $other = UserModel::factory()->create();

        $this->getJson("/v1/users/{$other->id}")
            ->assertForbidden();
    }
}
