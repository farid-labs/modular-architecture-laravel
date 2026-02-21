<?php

namespace Modules\Workspace\Tests\Feature;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Persistence\Models\ProjectModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use Modules\Workspace\Tests\TestCase;

class TaskTest extends TestCase
{
    private UserModel $owner;

    private WorkspaceModel $workspace;

    private ProjectModel $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = UserModel::factory()->create();
        $this->workspace = WorkspaceModel::factory()->active()->forOwner($this->owner)->create();
        $this->project = ProjectModel::factory()->active()->create(['workspace_id' => $this->workspace->id]);

        // ✅ FIX: Manually attach owner as member since factory doesn't do this
        $this->workspace->members()->attach($this->owner->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
    }

    public function test_create_task_success(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->postJson(route('tasks.store', $this->project->id), [
                'title' => 'Test Task',
                'description' => 'Test description',
                'priority' => 'high',
            ]);

        $response->assertCreated()
            ->assertJson([
                'message' => __('workspaces.task_created'),
            ])
            ->assertJsonStructure([
                'data' => ['id', 'title', 'project_id', 'status', 'priority'],
            ]);
    }

    public function test_update_task_success(): void
    {
        $task = TaskModel::factory()->pending()->create(['project_id' => $this->project->id]);
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->putJson(route('tasks.update', $task->id), [
                'title' => 'Updated Task Title',
                'status' => 'in_progress',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.task_updated'),
            ])
            ->assertJsonStructure([
                'data' => ['id', 'title', 'status'],
            ]);
    }

    public function test_delete_task_success(): void
    {
        $task = TaskModel::factory()->create(['project_id' => $this->project->id]);
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->deleteJson(route('tasks.destroy', $task->id));

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.task_deleted'),
            ]);
    }

    public function test_complete_task_success(): void
    {
        $task = TaskModel::factory()->pending()->create(['project_id' => $this->project->id]);
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->putJson(route('tasks.complete', $task->id));

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
     */
    public function test_get_task_by_id_success(): void
    {
        $task = TaskModel::factory()->pending()->highPriority()->create([
            'project_id' => $this->project->id,
            'title' => 'Test Task for Retrieval',
        ]);

        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('tasks.show', $task->id));

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
