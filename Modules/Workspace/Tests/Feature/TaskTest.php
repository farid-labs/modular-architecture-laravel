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
    }

    public function test_create_task_success(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'title' => 'Test Task',
                'description' => 'Test description',
                'priority' => 'high',
            ]);

        $response->assertCreated()
            ->assertJson([
                'message' => __('workspaces.task_created'),
            ]);
    }

    public function test_update_task_success(): void
    {
        $task = TaskModel::factory()->pending()->create(['project_id' => $this->project->id]);
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->putJson("/api/v1/tasks/{$task->id}", [
                'title' => 'Updated Task Title',
                'status' => 'in_progress',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.task_updated'),
            ]);
    }

    public function test_delete_task_success(): void
    {
        $task = TaskModel::factory()->create(['project_id' => $this->project->id]);
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.task_deleted'),
            ]);
    }

    public function test_complete_task_success(): void
    {
        $task = TaskModel::factory()->pending()->create(['project_id' => $this->project->id]);
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->putJson("/api/v1/tasks/{$task->id}/complete");

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.task_completed'),
            ])
            ->assertJsonPath('data.status', 'completed');
    }
}
