<?php

namespace Modules\Workspace\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Domain\Events\TaskCommentAdded;
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
}
