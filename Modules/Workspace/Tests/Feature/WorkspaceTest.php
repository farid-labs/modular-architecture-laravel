<?php

namespace Modules\Workspace\Tests\Feature;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use Modules\Workspace\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature test suite for Workspace API endpoints.
 *
 * Tests all workspace-related CRUD operations including:
 * - Create, read, update, and delete workspaces
 * - Add and remove workspace members
 * - List workspace members
 * - Workspace retrieval by slug
 * - Authorization and ownership validation
 *
 * All tests verify proper authentication, authorization,
 * response structure, and business logic enforcement.
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 *
 * @see \Modules\Workspace\Presentation\Controllers\WorkspaceController
 * @see \Modules\Workspace\Application\Services\WorkspaceService
 */
class WorkspaceTest extends TestCase
{
    /**
     * The workspace owner user model.
     */
    private UserModel $owner;

    /**
     * The workspace member user model.
     */
    private UserModel $member;

    /**
     * The workspace model for testing.
     */
    private WorkspaceModel $workspace;

    /**
     * Set up test fixtures before each test.
     *
     * Creates test users (owner and member) and an active workspace.
     * Manually attaches both users as workspace members with
     * appropriate roles for authorization testing.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create workspace owner user
        $this->owner = UserModel::factory()->create();

        // Create workspace member user
        $this->member = UserModel::factory()->create();

        // Create active workspace owned by the test user
        $this->workspace = WorkspaceModel::factory()
            ->active()
            ->forOwner($this->owner)
            ->create();

        // Attach owner as member with owner role
        $this->workspace->members()->attach($this->owner->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Attach member as member with member role
        $this->workspace->members()->attach($this->member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    /**
     * Test successful workspace update.
     *
     * Verifies that workspace owners can update workspace properties
     * with partial data and receive updated workspace information.
     *
     *
     * @test
     */
    #[Test]
    public function test_update_workspace_success(): void
    {
        // Generate authentication token for the workspace owner
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send PUT request to update the workspace
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->putJson(route('workspaces.update', $this->workspace->id), [
                'name' => 'Updated Workspace Name',
                'description' => 'Updated description',
            ]);

        // Assert successful update response with structure
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.updated'),
            ])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'description', 'status'],
            ]);
    }

    /**
     * Test successful workspace deletion.
     *
     * Verifies that workspace owners can delete workspaces
     * and the workspace is soft deleted from the database.
     *
     *
     * @test
     */
    #[Test]
    public function test_delete_workspace_success(): void
    {
        // Generate authentication token for the workspace owner
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send DELETE request to remove the workspace
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->deleteJson(route('workspaces.destroy', $this->workspace->id));

        // Assert successful deletion response
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.deleted'),
            ]);

        // Verify the workspace was soft deleted (still exists with deleted_at)
        $this->assertSoftDeleted('workspaces', ['id' => $this->workspace->id]);
    }

    /**
     * Test non-owner cannot update workspace.
     *
     * Verifies that workspace members (non-owners) cannot
     * update workspace properties (authorization check).
     *
     *
     * @test
     */
    #[Test]
    public function test_non_owner_cannot_update_workspace(): void
    {
        // Generate authentication token for a regular member (not owner)
        $token = $this->member->createToken('test-token')->plainTextToken;

        // Attempt to update workspace as non-owner
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->putJson(route('workspaces.update', $this->workspace->id), [
                'name' => 'Unauthorized Update',
            ]);

        // Assert request is forbidden with proper error message
        $response->assertForbidden()
            ->assertJson([
                'message' => __('workspaces.not_owner'),
            ]);
    }

    /**
     * Test listing workspace members.
     *
     * Verifies that workspace members can retrieve the list
     * of all workspace members with their roles and join dates.
     *
     *
     * @test
     */
    #[Test]
    public function test_list_workspace_members(): void
    {
        // Generate authentication token for the workspace owner
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send GET request to list workspace members
        // Use correct route name (index, not add)
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('workspaces.members.index', $this->workspace->id));

        // Assert response contains member list structure
        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'message',
            ]);
    }

    /**
     * Test retrieving a workspace by its slug.
     *
     * Verifies that authenticated users can fetch workspace details
     * using the unique slug identifier instead of numeric ID.
     * Tests complete workspace data structure in response.
     *
     *
     * @test
     */
    #[Test]
    public function test_get_workspace_by_slug_success(): void
    {
        // Generate authentication token for the workspace owner
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send GET request to retrieve workspace by slug
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('workspaces.show', $this->workspace->slug));

        // Assert response contains complete workspace data
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.retrieved'),
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'status',
                    'owner',
                    'members_count',
                    'projects_count',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /**
     * Test adding a new member to a workspace.
     *
     * Verifies that workspace owners can add users with specific roles
     * (owner, admin, member) to collaborate on the workspace.
     * Tests database state after member addition.
     *
     *
     * @test
     */
    #[Test]
    public function test_add_member_to_workspace_success(): void
    {
        // Create a new user to add as member
        $newUser = UserModel::factory()->create();

        // Generate authentication token for the workspace owner
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send POST request to add new member
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->postJson(route('workspaces.members.add', $this->workspace->id), [
                'user_id' => $newUser->id,
                'role' => 'member',
            ]);

        // Assert successful addition response
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.member_added'),
            ]);

        // Verify the user was actually added to the workspace
        // Check pivot table for correct membership record
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $newUser->id,
            'role' => 'member',
        ]);
    }

    /**
     * Test removing a member from a workspace.
     *
     * Verifies that workspace owners can remove users from the workspace,
     * revoking their access to projects and tasks.
     * Tests database state after member removal.
     *
     *
     * @test
     */
    #[Test]
    public function test_remove_member_from_workspace_success(): void
    {
        // Generate authentication token for the workspace owner
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send DELETE request to remove member
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->deleteJson(route('workspaces.members.remove', $this->workspace->id), [
                'user_id' => $this->member->id,
            ]);

        // Assert successful removal response
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.member_removed'),
            ]);

        // Verify the user was actually removed from the workspace
        // Check pivot table for removed membership record
        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->member->id,
        ]);
    }
}
