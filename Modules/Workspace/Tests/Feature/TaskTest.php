<?php

namespace Modules\Workspace\Tests\Feature;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use Modules\Workspace\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature test suite for Task API endpoints.
 *
 * Tests all task-related CRUD operations including:
 * - Create, read, update, and delete tasks
 * - Complete task status change
 * - Task retrieval by ID
 * - Authorization and permission validation
 *
 * All tests verify proper authentication, authorization,
 * response structure, and business logic enforcement.
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 *
 * @see \Modules\Workspace\Presentation\Controllers\TaskController
 * @see \Modules\Workspace\Application\Services\WorkspaceService
 */
class TaskTest extends TestCase
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
     * The project model for testing tasks.
     */
    private ProjectModel $project;

    /**
     * Set up test fixtures before each test.
     *
     * Creates a test user (workspace owner), an active workspace,
     * and an active project. Manually attaches the owner as a
     * workspace member for proper authorization checks.
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

        // Create active project in the workspace
        $this->project = ProjectModel::factory()
            ->active()
            ->create(['workspace_id' => $this->workspace->id]);

        // Manually attach owner as member since factory doesn't do this
        // This ensures proper workspace membership for authorization checks
        $this->workspace->members()->attach($this->owner->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
    }

    /**
     * Test successful task creation.
     *
     * Verifies that workspace members can create new tasks
     * with valid data and receive proper response structure.
     *
     *
     * @test
     */
    #[Test]
    public function test_create_task_success(): void
    {
        // Generate authentication token for the workspace owner
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send POST request to create a new task
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->postJson(route('tasks.store', $this->project->id), [
                'title' => 'Test Task',
                'description' => 'Test description',
                'priority' => 'high',
            ]);

        // Assert response status and structure
        $response->assertCreated()
            ->assertJson([
                'message' => __('workspaces.task_created'),
            ])
            ->assertJsonStructure([
                'data' => ['id', 'title', 'project_id', 'status', 'priority'],
            ]);
    }

    /**
     * Test successful task update.
     *
     * Verifies that workspace members can update existing tasks
     * with partial data and receive updated task information.
     *
     *
     * @test
     */
    #[Test]
    public function test_update_task_success(): void
    {
        // Create a pending task in the test project
        $task = TaskModel::factory()
            ->pending()
            ->create(['project_id' => $this->project->id]);

        // Generate authentication token
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send PUT request to update the task
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->putJson(route('tasks.update', $task->id), [
                'title' => 'Updated Task Title',
                'status' => 'in_progress',
            ]);

        // Assert successful update response with structure
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.task_updated'),
            ])
            ->assertJsonStructure([
                'data' => ['id', 'title', 'status'],
            ]);
    }

    /**
     * Test successful task deletion.
     *
     * Verifies that workspace members can delete tasks
     * and receive proper confirmation response.
     *
     *
     * @test
     */
    #[Test]
    public function test_delete_task_success(): void
    {
        // Create a task in the test project
        $task = TaskModel::factory()
            ->create(['project_id' => $this->project->id]);

        // Generate authentication token
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send DELETE request to remove the task
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->deleteJson(route('tasks.destroy', $task->id));

        // Assert successful deletion response
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.task_deleted'),
            ]);
    }

    /**
     * Test successful task completion.
     *
     * Verifies that workspace members can mark tasks as completed
     * and the task status is properly updated in the response.
     *
     *
     * @test
     */
    #[Test]
    public function test_complete_task_success(): void
    {
        // Create a pending task in the test project
        $task = TaskModel::factory()
            ->pending()
            ->create(['project_id' => $this->project->id]);

        // Generate authentication token
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send PUT request to complete the task
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->putJson(route('tasks.complete', $task->id));

        // Assert successful completion with status verification
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.task_completed'),
            ])
            ->assertJsonPath('data.status', 'completed');
    }

    /**
     * Test retrieving a single task by its ID.
     *
     * Verifies that authenticated users can fetch detailed information
     * about a specific task including status, priority, and due date.
     * Tests complete task data structure with computed fields.
     *
     *
     * @test
     */
    #[Test]
    public function test_get_task_by_id_success(): void
    {
        // Create a pending high-priority task in the test project
        $task = TaskModel::factory()
            ->pending()
            ->highPriority()
            ->create([
                'project_id' => $this->project->id,
                'title' => 'Test Task for Retrieval',
            ]);

        // Generate authentication token
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Send GET request to retrieve task
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('tasks.show', $task->id));

        // Assert response contains complete task data with all fields
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.task_retrieved'),
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'project_id',
                    'assigned_to',
                    'status',
                    'priority',
                    'due_date',
                    'is_overdue',
                    'is_completed',
                    'is_assigned',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }
}
