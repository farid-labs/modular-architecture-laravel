<?php

namespace Modules\Workspace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Infrastructure\Jobs\ProcessTaskAttachmentJob;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Infrastructure\Persistence\Models\WorkspaceModel;
use Modules\Workspace\Presentation\Controllers\TaskAttachmentController;
use Modules\Workspace\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for Task Attachment API endpoints.
 *
 * Comprehensive test suite covering:
 * - Attachment upload with validation (file count, size, type)
 * - Authorization enforcement (uploader ownership, workspace membership)
 * - Attachment lifecycle (create, list, delete)
 * - Background job processing for uploaded files
 * - Cache invalidation after mutations
 *
 * @covers \Modules\Workspace\Presentation\Controllers\TaskAttachmentController
 * @covers \Modules\Workspace\Application\Services\WorkspaceService
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
#[CoversClass(TaskAttachmentController::class)]
#[CoversClass(WorkspaceService::class)]
class TaskAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;

    private WorkspaceModel $workspace;

    private TaskModel $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user
        $this->user = UserModel::factory()->create();

        // Create workspace and add user as member with owner role
        $this->workspace = WorkspaceModel::factory()->create(['owner_id' => $this->user->id]);
        $this->workspace->members()->attach($this->user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Create project within workspace
        $project = $this->workspace->projects()->create([
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => 'active',
        ]);

        // Create task within project
        $this->task = $project->tasks()->create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending',
            'priority' => 'medium',
        ]);
    }

    #[Test]
    public function test_member_can_upload_attachment_and_job_is_queued(): void
    {
        Queue::fake();

        // Use PDF file to avoid GD extension dependency
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => $this->task->id]), [
                'files' => [$file],
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'task_id',
                        'file_name',
                        'file_path',
                        'file_type',
                        'file_size',
                        'uploaded_by',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.file_name', 'document.pdf')
            ->assertJsonPath('data.0.file_type', 'application/pdf');

        // Verify background processing job was queued (without accessing private property)
        Queue::assertPushed(ProcessTaskAttachmentJob::class, function ($job) {
            return $job instanceof ProcessTaskAttachmentJob;
        });

        $attachmentId = $response->json('data.0.id');
        $this->assertDatabaseHas('task_attachments', [
            'id' => $attachmentId,
            'task_id' => $this->task->id,
            'uploaded_by' => $this->user->id,
            'file_name' => 'document.pdf',
        ]);
    }

    #[Test]
    public function test_upload_attachment_validation(): void
    {
        // Test missing files parameter
        $response = $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => $this->task->id]), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['files']);

        // Test file exceeding size limit (10MB = 10240KB)
        $largeFile = UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf');
        $response = $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => $this->task->id]), [
                'files' => [$largeFile],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['files.0']);

        // Test invalid file type (executable not allowed)
        $invalidFile = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');
        $response = $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => $this->task->id]), [
                'files' => [$invalidFile],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['files.0']);

        // Test exceeding maximum file count (4 files > max 3)
        $files = [
            UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('file2.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('file3.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('file4.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => $this->task->id]), [
                'files' => $files,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['files']);

        // Test minimum file count violation (0 files)
        $response = $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => $this->task->id]), [
                'files' => [],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['files']);
    }

    #[Test]
    public function test_delete_attachment_success(): void
    {
        // Upload attachment first using allowed file type
        $file = UploadedFile::fake()->create('to-delete.pdf', 100, 'application/pdf');
        $uploadResponse = $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => $this->task->id]), [
                'files' => [$file],
            ]);

        $uploadResponse->assertCreated();
        $attachmentId = $uploadResponse->json('data.0.id');
        $filePath = $uploadResponse->json('data.0.file_path');

        // Verify attachment exists before deletion
        $this->assertDatabaseHas('task_attachments', ['id' => $attachmentId]);

        // Delete the attachment
        $response = $this->actingAs($this->user)
            ->deleteJson(route('tasks.attachments.destroy', [
                'taskId' => $this->task->id,
                'attachmentId' => $attachmentId,
            ]));

        $response->assertOk()
            ->assertJson(['message' => __('workspaces.attachment_deleted')]);

        // Verify attachment was soft-deleted from database
        $this->assertSoftDeleted('task_attachments', ['id' => $attachmentId]);
    }

    #[Test]
    public function test_non_uploader_cannot_delete_attachment(): void
    {
        // Create another workspace member with explicit type hint
        /** @var UserModel $otherMember */
        $otherMember = UserModel::factory()->create();
        $this->workspace->members()->attach($otherMember->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // Upload attachment as original user
        $file = UploadedFile::fake()->create('protected.pdf', 100, 'application/pdf');
        $uploadResponse = $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => $this->task->id]), [
                'files' => [$file],
            ]);

        $uploadResponse->assertCreated();
        $attachmentId = $uploadResponse->json('data.0.id');

        // Attempt deletion as different user (non-uploader)
        $response = $this->actingAs($otherMember)
            ->deleteJson(route('tasks.attachments.destroy', [
                'taskId' => $this->task->id,
                'attachmentId' => $attachmentId,
            ]));

        $response->assertForbidden()
            ->assertJson(['message' => __('workspaces.attachment_delete_forbidden')]);

        // Verify attachment still exists in database
        $this->assertDatabaseHas('task_attachments', ['id' => $attachmentId]);
    }

    #[Test]
    public function test_list_attachments_by_task_success(): void
    {
        // Upload multiple attachments using ONLY allowed file types
        $files = [
            UploadedFile::fake()->create('report.pdf', 500, 'application/pdf'),
            UploadedFile::fake()->create('diagram.png', 300, 'image/png'),
            UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf'), // Changed from .txt to .pdf
        ];

        $uploadResponse = $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => $this->task->id]), [
                'files' => $files,
            ]);

        $uploadResponse->assertCreated();
        $uploadResponse->assertJsonCount(3, 'data');

        // List attachments
        $response = $this->actingAs($this->user)
            ->getJson(route('tasks.attachments.index', ['taskId' => $this->task->id]));

        $response->assertOk()
            ->assertJson(['message' => __('workspaces.attachments_retrieved')])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'task_id',
                        'file_name',
                        'file_path',
                        'file_type',
                        'file_size',
                        'uploaded_by',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function test_non_member_cannot_view_attachments(): void
    {
        // Create user with no workspace membership
        /** @var UserModel $nonMember */
        $nonMember = UserModel::factory()->create();

        // Attempt to list attachments without authorization
        $response = $this->actingAs($nonMember)
            ->getJson(route('tasks.attachments.index', ['taskId' => $this->task->id]));

        // Assert forbidden response with specific error message
        // NOW PASSES: Service layer properly enforces authorization
        $response->assertForbidden()
            ->assertJson(['message' => __('workspaces.not_member_of_project')]);
    }

    #[Test]
    public function test_task_not_found_returns_404(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $response = $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => 99999]), [
                'files' => [$file],
            ]);

        $response->assertNotFound()
            ->assertJson(['message' => __('workspaces.task_not_found', ['id' => 99999])]);
    }

    #[Test]
    public function test_cache_invalidated_after_upload(): void
    {
        // Prime the cache with initial empty state
        $this->actingAs($this->user)
            ->getJson(route('tasks.attachments.index', ['taskId' => $this->task->id]));

        // Upload new attachment
        $file = UploadedFile::fake()->create('new-file.pdf', 100, 'application/pdf');
        $this->actingAs($this->user)
            ->postJson(route('tasks.attachments.store', ['taskId' => $this->task->id]), [
                'files' => [$file],
            ])
            ->assertCreated();

        // Verify cache was invalidated and new attachment appears immediately
        $response = $this->actingAs($this->user)
            ->getJson(route('tasks.attachments.index', ['taskId' => $this->task->id]));

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.file_name', 'new-file.pdf');
    }
}
