<?php

namespace Modules\Workspace\Tests\Feature;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use Modules\Workspace\Tests\TestCase;

class WorkspaceTest extends TestCase
{
    private UserModel $owner;

    private UserModel $member;

    private WorkspaceModel $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = UserModel::factory()->create();
        $this->member = UserModel::factory()->create();
        $this->workspace = WorkspaceModel::factory()->active()->forOwner($this->owner)->create();

        // Attach owner as member
        $this->workspace->members()->attach($this->owner->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Attach member
        $this->workspace->members()->attach($this->member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    public function test_update_workspace_success(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->putJson(route('workspaces.update', $this->workspace->id), [
                'name' => 'Updated Workspace Name',
                'description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.updated'),
            ])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'description', 'status'],
            ]);
    }

    public function test_delete_workspace_success(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->deleteJson(route('workspaces.destroy', $this->workspace->id));

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.deleted'),
            ]);

        $this->assertSoftDeleted('workspaces', ['id' => $this->workspace->id]);
    }

    public function test_non_owner_cannot_update_workspace(): void
    {
        $token = $this->member->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->putJson(route('workspaces.update', $this->workspace->id), [
                'name' => 'Unauthorized Update',
            ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => __('workspaces.not_owner'),
            ]);
    }

    public function test_list_workspace_members(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Use correct route name (index, not add)
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('workspaces.members.index', $this->workspace->id));

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
     */
    public function test_get_workspace_by_slug_success(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('workspaces.show', $this->workspace->slug));

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
     */
    public function test_add_member_to_workspace_success(): void
    {
        $newUser = UserModel::factory()->create();
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->postJson(route('workspaces.members.add', $this->workspace->id), [
                'user_id' => $newUser->id,
                'role' => 'member',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.member_added'),
            ]);

        // Verify the user was actually added to the workspace
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
     */
    public function test_remove_member_from_workspace_success(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->deleteJson(route('workspaces.members.remove', $this->workspace->id), [
                'user_id' => $this->member->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.member_removed'),
            ]);

        // Verify the user was actually removed from the workspace
        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->member->id,
        ]);
    }
}
