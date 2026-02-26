<?php

namespace Modules\Users\Tests\Feature\Presentation\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Users\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for UserController API endpoints.
 * Covers CRUD operations, self-management, and permission checks.
 */
#[CoversClass(UserControllerTest::class)]
class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Helper: Authenticate a user (optionally as admin).
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

    #[Test]
    public function test_can_list_users(): void
    {
        // Admin authentication assumed for listing users
        $this->authenticate(admin: true);

        // Arrange: create additional users
        UserModel::factory()->count(3)->create();

        $this->getJson(route('users.admin.index'))
            ->assertOk()
            ->assertJsonCount(4, 'data'); // 3 + 1 admin
    }

    #[Test]
    public function test_can_show_single_user(): void
    {
        // Admin can view any user
        $this->authenticate(admin: true);

        $user = UserModel::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $this->getJson(route('users.admin.show', ['user' => $user->id]))
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                ],
            ]);
    }

    #[Test]
    public function test_can_create_user(): void
    {
        $this->authenticate(admin: true);

        $data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123',
        ];

        $this->postJson(route('users.admin.store'), $data)
            ->assertCreated()
            ->assertJson(['data' => ['email' => $data['email']]]);

        $this->assertDatabaseHas('users', ['email' => $data['email']]);
    }

    #[Test]
    public function test_cannot_create_user_with_existing_email(): void
    {
        $this->authenticate(admin: true);

        $existing = UserModel::factory()->create();

        $this->postJson(route('users.admin.store'), [
            'name' => $this->faker->name(),
            'email' => $existing->email,
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function test_can_update_own_user(): void
    {
        $auth = $this->authenticate();

        $this->putJson(route('users.admin.update', ['user' => $auth->id]), [
            'name' => 'My New Name',
            'email' => $this->faker->unique()->safeEmail(),
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $auth->id,
            'name' => 'My New Name',
        ]);
    }

    #[Test]
    public function test_cannot_update_other_user_without_permission(): void
    {
        $this->authenticate();
        $other = UserModel::factory()->create();

        $this->putJson(route('users.admin.update', ['user' => $other->id]), [
            'name' => 'Hacked Name',
            'email' => $this->faker->unique()->safeEmail(),
        ])->assertForbidden();
    }

    #[Test]
    public function test_can_delete_own_user(): void
    {
        $auth = $this->authenticate();

        $this->deleteJson(route('users.admin.destroy', ['user' => $auth->id]))
            ->assertOk();
    }

    #[Test]
    public function test_cannot_delete_other_user_without_permission(): void
    {
        $this->authenticate();
        $other = UserModel::factory()->create();

        $this->deleteJson(route('users.admin.destroy', ['user' => $other->id]))
            ->assertForbidden();
    }

    #[Test]
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $this->getJson(route('users.admin.index'))
            ->assertUnauthorized();
    }

    #[Test]
    public function test_can_show_own_user(): void
    {
        $auth = $this->authenticate();

        // Self access test via admin route (assuming policy allows self)
        $this->getJson(route('users.admin.show', ['user' => $auth->id]))
            ->assertOk();
    }

    #[Test]
    public function test_cannot_show_other_user_without_permission(): void
    {
        $this->authenticate();
        $other = UserModel::factory()->create();

        $this->getJson(route('users.admin.show', ['user' => $other->id]))
            ->assertForbidden();
    }
}
