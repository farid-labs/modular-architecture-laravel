<?php

namespace Modules\Workspace\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Jobs\ProcessTaskAttachmentJob;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Tests\TestCase;

class TaskAttachmentTest extends TestCase
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
            $this->fail('Created task has no associated project. Ensure factory sets project relation.');
        }

        $workspaceId = $project->workspace_id;

        $this->member->workspaces()->attach($workspaceId, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    public function test_member_can_upload_attachment_and_job_is_queued(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $token = $this->member->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->postJson(route('tasks.attachments.store', $this->taskId), [
            'file' => $file,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'file_name']]);

        Queue::assertPushed(ProcessTaskAttachmentJob::class);
    }
}
