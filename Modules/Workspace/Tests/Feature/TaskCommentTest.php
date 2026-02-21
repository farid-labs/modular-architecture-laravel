<?php

namespace Modules\Workspace\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Events\TaskCommentAdded;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskCommentModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Tests\TestCase;

class TaskCommentTest extends TestCase
{
    private UserModel $member;

    private int $taskId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = UserModel::factory()->create();

        $taskModel = TaskModel::factory()->create();
        $this->taskId = $taskModel->id;

        $project = $taskModel->project;
        if (! $project) {
            $this->fail('Task has no associated project');
        }
        $workspaceId = $project->workspace_id;

        $this->member->workspaces()->attach($workspaceId, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    public function test_member_can_add_comment_and_event_is_fired(): void
    {
        Event::fake([TaskCommentAdded::class]);

        $response = $this->actingAs($this->member)
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => 'This is a professional comment for testing.',
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'comment', 'user_id']]);

        Event::assertDispatched(TaskCommentAdded::class, function ($event) {
            return $event->actorId === $this->member->id;
        });
    }

    public function test_non_member_cannot_add_comment(): void
    {
        /** @var UserModel $outsider */
        $outsider = UserModel::factory()->create();

        $response = $this->actingAs($outsider)
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => 'Test from outsider',
            ]);

        $response->assertForbidden();
    }

    public function test_add_comment_validation(): void
    {
        $token = $this->member->createToken('test-token')->plainTextToken;

        // Test empty comment
        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => '',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('comment');

        // Test comment too short
        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => 'ab',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('comment');

        // Test comment too long
        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => str_repeat('a', 2001),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('comment');
    }

    public function test_update_comment_validation(): void
    {
        $token = $this->member->createToken('test-token')->plainTextToken;

        // Create a comment first
        $createResponse = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => 'Initial comment',
            ]);

        $commentId = $createResponse->json('data.id');

        // Test update with invalid data
        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->putJson(route('comments.update', $commentId), [
                'comment' => 'ab',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('comment');
    }

    /**
     * Test listing all comments for a task.
     *
     * Verifies that workspace members can retrieve all comments
     * associated with a specific task they have access to.
     */
    public function test_list_comments_by_task_success(): void
    {
        // Create multiple comments for the task
        TaskCommentModel::factory()->count(3)->create(['task_id' => $this->taskId]);

        $token = $this->member->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('tasks.comments.index', $this->taskId));

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.comments_retrieved'),
            ])
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'task_id',
                    'user_id',
                    'comment',
                    'created_at',
                    'updated_at',
                ]],
            ]);
    }

    /**
     * Test deleting a comment successfully.
     *
     * Verifies that comment authors can delete their own comments,
     * and the comment is permanently removed from the system.
     */
    public function test_delete_comment_success(): void
    {
        // First create a comment
        $createResponse = $this->actingAs($this->member)
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => 'Comment to be deleted',
            ]);

        $commentId = $createResponse->json('data.id');

        $token = $this->member->createToken('test-token')->plainTextToken;

        // Delete the comment
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->deleteJson(route('tasks.comments.destroy', [
                'taskId' => $this->taskId,
                'commentId' => $commentId,
            ]));

        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.comment_deleted'),
            ]);

        // Verify the comment was soft deleted
        $this->assertSoftDeleted('task_comments', ['id' => $commentId]);
    }
}
