<?php

namespace Modules\Workspace\Tests\Feature;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
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

        // Manually attach owner as member since factory doesn't do this
        $this->workspace->members()->attach($this->owner->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
    }

    public function test_create_project_success(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->postJson(route('projects.create', $this->workspace->id), [
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

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->putJson(route('projects.update', $project->id), [
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

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->deleteJson(route('projects.destroy', $project->id));

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.project_deleted'),
            ]);
    }

    public function test_list_tasks_by_project(): void
    {
        $project = ProjectModel::factory()->create(['workspace_id' => $this->workspace->id]);

        //  Create tasks for the project (so data array is not empty)
        TaskModel::factory()->count(3)->create(['project_id' => $project->id]);

        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('tasks.index', $project->id));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'title', 'status', 'priority']],
                'message',
            ]);
    }

    /**
     * Test listing all projects within a workspace.
     *
     * Verifies that workspace members can retrieve all projects
     * belonging to a specific workspace they have access to.
     */
    public function test_list_projects_by_workspace_success(): void
    {
        // Create multiple projects for the workspace
        ProjectModel::factory()->count(3)->create(['workspace_id' => $this->workspace->id]);

        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('projects.index', $this->workspace->id));

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.projects_retrieved'),
            ])
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'description',
                    'workspace_id',
                    'status',
                    'is_active',
                    'created_at',
                    'updated_at',
                ]],
            ]);
    }

    /**
     * Test retrieving a single project by its ID.
     *
     * Verifies that authenticated users can fetch detailed information
     * about a specific project using its unique identifier.
     */
    public function test_get_project_by_id_success(): void
    {
        $project = ProjectModel::factory()->active()->create(['workspace_id' => $this->workspace->id]);
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('projects.show', $project->id));

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.project_retrieved'),
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'workspace_id',
                    'status',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }
}
