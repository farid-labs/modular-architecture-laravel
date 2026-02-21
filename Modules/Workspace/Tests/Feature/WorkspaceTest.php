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

        $this->workspace->members()->attach($this->member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    public function test_update_workspace_success(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
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

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
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

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->putJson(route('workspaces.update', $this->workspace->id), [
                'name' => 'Unauthorized Update',
            ]);

        $response->assertForbidden();
    }

    public function test_list_workspace_members(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson(route('workspaces.members.add', $this->workspace->id));

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'message',
            ]);
    }
}
