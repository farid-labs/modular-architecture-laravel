<?php

namespace Modules\Workspace\Tests\Feature;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use Modules\Workspace\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature test suite for Project API endpoints.
 *
 * Tests all project-related CRUD operations including:
 * - Create, read, update, and delete projects
 * - List projects within a workspace
 * - List tasks belonging to a project
 * - Authorization and permission validation
 *
 * All tests verify proper authentication, authorization,
 * response structure, and business logic enforcement.
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 *
 * @see \Modules\Workspace\Presentation\Controllers\ProjectController
 * @see \Modules\Workspace\Application\Services\WorkspaceService
 */
class ProjectTest extends TestCase
{
    /**
     * The workspace owner user model.
     */
    private UserModel $owner;

    /**
     * The workspace model for testing.
     */
    private WorkspaceModel $workspace;

    /**
     * Set up test fixtures before each test.
     *
     * Creates a test user (workspace owner) and an active workspace.
     * Manually attaches the owner as a workspace member since the
     * factory doesn't handle pivot table relationships automatically.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create workspace owner user
        $this->owner = UserModel::factory()->create();

        // Create active workspace owned by the test user
        $this->workspace = WorkspaceModel::factory()
            ->active()
            ->forOwner($this->owner)
            ->create();

        // Manually attach owner as member since factory doesn't do this
        // This ensures proper workspace membership for authorization checks
        $this->workspace->members()->attach($this->owner->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
    }

    /**
     * Test successful project creation.
     *
     * Verifies that workspace members can create new projects
     * with valid data and receive proper response structure.
     *
     *
     * @test
     */
    #[Test]
    public function test_create_project_success(): void
    {
        // Generate authentication token for the workspace owner
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send POST request to create a new project
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->postJson(route('projects.create', $this->workspace->id), [
                'name' => 'Test Project',
                'description' => 'Test description',
            ]);

        // Assert response status and structure
        $response->assertCreated()
            ->assertJson([
                'message' => __('workspaces.project_created'),
            ])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'workspace_id', 'status'],
            ]);
    }

    /**
     * Test successful project update.
     *
     * Verifies that workspace members can update existing projects
     * with partial data and receive updated project information.
     *
     *
     * @test
     */
    #[Test]
    public function test_update_project_success(): void
    {
        // Create an active project in the test workspace
        $project = ProjectModel::factory()
            ->active()
            ->create(['workspace_id' => $this->workspace->id]);

        // Generate authentication token
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send PUT request to update the project
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->putJson(route('projects.update', $project->id), [
                'name' => 'Updated Project Name',
            ]);

        // Assert successful update response
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.project_updated'),
            ]);
    }

    /**
     * Test successful project deletion.
     *
     * Verifies that workspace members can delete projects
     * and receive proper confirmation response.
     *
     *
     * @test
     */
    #[Test]
    public function test_delete_project_success(): void
    {
        // Create a project in the test workspace
        $project = ProjectModel::factory()
            ->create(['workspace_id' => $this->workspace->id]);

        // Generate authentication token
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send DELETE request to remove the project
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->deleteJson(route('projects.destroy', $project->id));

        // Assert successful deletion response
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.project_deleted'),
            ]);
    }

    /**
     * Test listing tasks by project.
     *
     * Verifies that workspace members can retrieve all tasks
     * belonging to a specific project with proper structure.
     *
     *
     * @test
     */
    #[Test]
    public function test_list_tasks_by_project(): void
    {
        // Create a project in the test workspace
        $project = ProjectModel::factory()
            ->create(['workspace_id' => $this->workspace->id]);

        // Create tasks for the project (so data array is not empty)
        // This ensures the response contains actual task data
        TaskModel::factory()
            ->count(3)
            ->create(['project_id' => $project->id]);

        // Generate authentication token
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send GET request to list tasks
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('tasks.index', $project->id));

        // Assert response structure contains task data
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
     * Tests response structure includes all expected fields.
     *
     *
     * @test
     */
    #[Test]
    public function test_list_projects_by_workspace_success(): void
    {
        // Create multiple projects for the workspace
        ProjectModel::factory()
            ->count(3)
            ->create(['workspace_id' => $this->workspace->id]);

        // Generate authentication token
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send GET request to list projects
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('projects.index', $this->workspace->id));

        // Assert response contains project list with all fields
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
     * Tests complete project data structure in response.
     *
     *
     * @test
     */
    #[Test]
    public function test_get_project_by_id_success(): void
    {
        // Create an active project in the test workspace
        $project = ProjectModel::factory()
            ->active()
            ->create(['workspace_id' => $this->workspace->id]);

        // Generate authentication token
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send GET request to retrieve project
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('projects.show', $project->id));

        // Assert response contains complete project data
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
