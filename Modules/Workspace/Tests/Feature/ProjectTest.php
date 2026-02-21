<?php

namespace Modules\Workspace\Tests\Feature;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use Modules\Workspace\Tests\TestCase;

class ProjectTest extends TestCase
{
    private UserModel $owner;

    private WorkspaceModel $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = UserModel::factory()->create();
        $this->workspace = WorkspaceModel::factory()->active()->forOwner($this->owner)->create();
    }

    public function test_create_project_success(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson("/api/v1/workspaces/{$this->workspace->id}/projects", [
                'name' => 'Test Project',
                'description' => 'Test description',
            ]);

        $response->assertCreated()
            ->assertJson([
                'message' => __('workspaces.project_created'),
            ])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'workspace_id', 'status'],
            ]);
    }

    public function test_update_project_success(): void
    {
        $project = ProjectModel::factory()->active()->create(['workspace_id' => $this->workspace->id]);
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->putJson("/api/v1/projects/{$project->id}", [
                'name' => 'Updated Project Name',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.project_updated'),
            ]);
    }

    public function test_delete_project_success(): void
    {
        $project = ProjectModel::factory()->create(['workspace_id' => $this->workspace->id]);
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.project_deleted'),
            ]);
    }

    public function test_list_tasks_by_project(): void
    {
        $project = ProjectModel::factory()->create(['workspace_id' => $this->workspace->id]);
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson("/api/v1/projects/{$project->id}/tasks");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'title', 'status', 'priority']],
                'message',
            ]);
    }
}
