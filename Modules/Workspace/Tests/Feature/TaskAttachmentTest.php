<?php

namespace Modules\Workspace\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Infrastructure\Jobs\ProcessTaskAttachmentJob;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskModel;
use Modules\Workspace\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature test suite for Task Attachment API endpoints.
 *
 * Tests all attachment-related operations including:
 * - Upload file attachments to tasks
 * - List attachments for a task
 * - Delete attachments
 * - File validation (type, size)
 * - Background job queueing for processing
 *
 * All tests verify proper authentication, file validation,
 * storage handling, and response structure.
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 *
 * @see \Modules\Workspace\Presentation\Controllers\TaskAttachmentController
 * @see \Modules\Workspace\Infrastructure\Jobs\ProcessTaskAttachmentJob
 */
class TaskAttachmentTest extends TestCase
{
    /**
     * The workspace member user model.
     */
    private UserModel $member;

    /**
     * The task ID for testing attachments.
     */
    private int $taskId;

    /**
     * Set up test fixtures before each test.
     *
     * Creates a test user (workspace member) and a task with
     * associated project and workspace. Ensures proper membership
     * for authorization checks on attachment operations.
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
            $this->fail('Created task has no associated project. Ensure factory sets project relation.');
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
     * Test successful attachment upload with job queueing.
     *
     * Verifies that workspace members can upload file attachments
     * and that the background processing job is properly queued.
     *
     *
     * @test
     */
    #[Test]
    public function test_member_can_upload_attachment_and_job_is_queued(): void
    {
        // Fake the queue to prevent actual job execution
        Queue::fake();

        // Create a fake PDF file for upload (500KB)
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        // Generate authentication token
        $token = $this->member->createToken('test-token')->plainTextToken;

        // Send POST request to upload attachment
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])->postJson(route('tasks.attachments.store', $this->taskId), [
            'file' => $file,
        ]);

        // Assert successful upload and job queueing
        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'file_name']]);

        // Verify background processing job was queued
        Queue::assertPushed(ProcessTaskAttachmentJob::class);
    }

    /**
     * Test attachment upload validation.
     *
     * Verifies that the API properly validates file uploads:
     * - Rejects requests without a file
     * - Rejects files exceeding size limit (10MB)
     *
     *
     * @test
     */
    #[Test]
    public function test_upload_attachment_validation(): void
    {
        // Generate authentication token
        $token = $this->member->createToken('test-token')->plainTextToken;

        // Test missing file - should return validation error
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->postJson(route('tasks.attachments.store', $this->taskId), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        // Test file too large (create fake file > 10MB)
        // 10240 KB = 10MB, so 10240 * 2 = 20MB (exceeds limit)
        $largeFile = UploadedFile::fake()->create('large.pdf', 10240 * 2, 'application/pdf');

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->postJson(route('tasks.attachments.store', $this->taskId), [
                'file' => $largeFile,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    /**
     * Test successful attachment deletion.
     *
     * Verifies that attachment uploaders can delete their own
     * attachments and receive proper confirmation response.
     *
     *
     * @test
     */
    #[Test]
    public function test_delete_attachment_success(): void
    {
        // Generate authentication token
        $token = $this->member->createToken('test-token')->plainTextToken;

        // Create a fake PDF file for upload
        $file = UploadedFile::fake()->create('test.pdf', 500, 'application/pdf');

        // First upload an attachment
        $uploadResponse = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->postJson(route('tasks.attachments.store', $this->taskId), [
                'file' => $file,
            ]);

        // Extract attachment ID from upload response
        $attachmentId = $uploadResponse->json('data.id');

        // Delete the attachment
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->deleteJson(route('tasks.attachments.destroy', [
                'taskId' => $this->taskId,
                'attachmentId' => $attachmentId,
            ]));

        // Assert successful deletion response
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.attachment_deleted'),
            ]);
    }

    /**
     * Test listing all attachments for a task.
     *
     * Verifies that workspace members can retrieve all file attachments
     * associated with a specific task they have access to.
     * Tests response structure includes all attachment metadata fields.
     *
     *
     * @test
     */
    #[Test]
    public function test_list_attachments_by_task_success(): void
    {
        // Generate authentication token
        $token = $this->member->createToken('test-token')->plainTextToken;

        // Create multiple test files of different types
        $files = [
            UploadedFile::fake()->create('document1.pdf', 500, 'application/pdf'),
            UploadedFile::fake()->create('image1.png', 300, 'image/png'),
            UploadedFile::fake()->create('document2.pdf', 600, 'application/pdf'),
        ];

        // Upload each file as an attachment
        foreach ($files as $file) {
            $this->withHeaders([
                'Authorization' => "Bearer $token",
                'Accept' => 'application/json',
            ])
                ->postJson(route('tasks.attachments.store', $this->taskId), [
                    'file' => $file,
                ]);
        }

        // List all attachments
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->getJson(route('tasks.attachments.index', $this->taskId));

        // Assert response contains attachment list with all metadata
        $response->assertOk()
            ->assertJson([
                'message' => __('workspaces.attachments_retrieved'),
            ])
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'task_id',
                    'file_name',
                    'file_path',
                    'file_type',
                    'file_size',
                    'uploaded_by',
                    'created_at',
                    'updated_at',
                ]],
            ]);
    }
}
