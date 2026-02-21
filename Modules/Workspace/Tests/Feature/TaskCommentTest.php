<?php

namespace Modules\Workspace\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Events\TaskCommentAdded;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskCommentModel;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature test suite for Task Comment API endpoints.
 *
 * Tests all comment-related operations including:
 * - Add comments to tasks
 * - List comments for a task
 * - Update existing comments
 * - Delete comments
 * - Event dispatching for real-time notifications
 * - Authorization and validation rules
 *
 * All tests verify proper authentication, authorization,
 * comment validation, and event broadcasting.
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 *
 * @see \Modules\Workspace\Presentation\Controllers\TaskCommentController
 * @see \Modules\Workspace\Domain\Events\TaskCommentAdded
 */
class TaskCommentTest extends TestCase
{
    /**
     * The workspace member user model.
     */
    private UserModel $member;

    /**
     * The task ID for testing comments.
     */
    private int $taskId;

    /**
     * Set up test fixtures before each test.
     *
     * Creates a test user (workspace member) and a task with
     * associated project and workspace. Ensures proper membership
     * for authorization checks on comment operations.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create workspace member user
        $this->member = UserModel::factory()->create();

        // Create a task with associated project and workspace
        $taskModel = TaskModel::factory()->create();
        $this->taskId = $taskModel->id;

        // Get the associated project
        $project = $taskModel->project;

        // Fail test if task has no project (factory configuration issue)
        if (! $project) {
            $this->fail('Task has no associated project');
        }

        // Get workspace ID from project
        $workspaceId = $project->workspace_id;

        // Attach member to workspace for authorization
        $this->member->workspaces()->attach($workspaceId, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    /**
     * Test successful comment creation with event dispatching.
     *
     * Verifies that workspace members can add comments to tasks
     * and that the TaskCommentAdded event is properly dispatched
     * for real-time notifications.
     *
     *
     * @test
     */
    #[Test]
    public function test_member_can_add_comment_and_event_is_fired(): void
    {
        // Fake event dispatcher to prevent actual broadcasting
        Event::fake([TaskCommentAdded::class]);

        // Send POST request to add a comment
        $response = $this->actingAs($this->member)
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => 'This is a professional comment for testing.',
            ]);

        // Assert successful creation and response structure
        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'comment', 'user_id']]);

        // Verify event was dispatched with correct actor ID
        Event::assertDispatched(TaskCommentAdded::class, function ($event) {
            return $event->actorId === $this->member->id;
        });
    }

    /**
     * Test non-member cannot add comment.
     *
     * Verifies that users who are not workspace members
     * cannot add comments to tasks (authorization check).
     *
     *
     * @test
     */
    #[Test]
    public function test_non_member_cannot_add_comment(): void
    {
        // Create an outsider user (not a workspace member)
        /** @var UserModel $outsider */
        $outsider = UserModel::factory()->create();

        // Attempt to add comment as outsider
        $response = $this->actingAs($outsider)
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => 'Test from outsider',
            ]);

        // Assert request is forbidden
        $response->assertForbidden();
    }

    /**
     * Test comment validation rules.
     *
     * Verifies that the API properly validates comment content:
     * - Rejects empty comments
     * - Rejects comments shorter than 3 characters
     * - Rejects comments longer than 2000 characters
     *
     *
     * @test
     */
    #[Test]
    public function test_add_comment_validation(): void
    {
        // Generate authentication token
        $token = $this->member->createToken('test-token')->plainTextToken;

        // Test empty comment - should return validation error
        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => '',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('comment');

        // Test comment too short (less than 3 characters)
        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => 'ab',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('comment');

        // Test comment too long (exceeds 2000 characters)
        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => str_repeat('a', 2001),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('comment');
    }

    /**
     * Test comment update validation.
     *
     * Verifies that comment updates are properly validated
     * with the same rules as comment creation.
     *
     *
     * @test
     */
    #[Test]
    public function test_update_comment_validation(): void
    {
        // Generate authentication token
        $token = $this->member->createToken('test-token')->plainTextToken;

        // Create a comment first
        $createResponse = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => 'Initial comment',
            ]);

        // Extract comment ID from creation response
        $commentId = $createResponse->json('data.id');

        // Test update with invalid data (too short)
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
     * Tests response structure includes all comment fields.
     *
     *
     * @test
     */
    #[Test]
    public function test_list_comments_by_task_success(): void
    {
        // Create multiple comments for the task
        TaskCommentModel::factory()
            ->count(3)
            ->create(['task_id' => $this->taskId]);

        // Generate authentication token
        $token = $this->member->createToken('test-token')->plainTextToken;

        // Send GET request to list comments
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('tasks.comments.index', $this->taskId));

        // Assert response contains comment list with all fields
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
     * Test successful comment deletion.
     *
     * Verifies that comment authors can delete their own comments,
     * and the comment is soft deleted from the system.
     * Tests database state after deletion.
     *
     *
     * @test
     */
    #[Test]
    public function test_delete_comment_success(): void
    {
        // First create a comment
        $createResponse = $this->actingAs($this->member)
            ->postJson(route('tasks.comments.store', $this->taskId), [
                'comment' => 'Comment to be deleted',
            ]);

        // Extract comment ID from creation response
        $commentId = $createResponse->json('data.id');

        // Generate authentication token
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

        // Assert successful deletion response
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.comment_deleted'),
            ]);

        // Verify the comment was soft deleted (still exists in DB with deleted_at)
        $this->assertSoftDeleted('task_comments', ['id' => $commentId]);
    }
}
