<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Presentation\Requests\StoreTaskAttachmentRequest;
use Modules\Workspace\Presentation\Resources\TaskAttachmentResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Controller responsible for managing task attachments.
 *
 * Provides RESTful endpoints to list, upload, and delete file attachments for tasks.
 * All operations require authentication via Sanctum token and proper authorization.
 * Attachments are validated for file type (images, PDFs) and size (max 10MB).
 *
 * Key Features:
 * - List all attachments for a specific task
 * - Upload new file attachments with validation
 * - Delete attachments (uploader only)
 * - Automatic file storage and metadata tracking
 * - Event dispatching for real-time notifications
 *
 * @see WorkspaceService For business logic implementation
 * @see TaskAttachmentResource For API response formatting
 * @see StoreTaskAttachmentRequest For upload validation rules
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
#[OA\Tag(name: 'Task Attachments', description: 'Endpoints for managing file attachments on tasks')]
class TaskAttachmentController extends Controller
{
    /**
     * Create a new TaskAttachmentController instance.
     *
     * @param  WorkspaceService  $service  The workspace service dependency for attachment operations
     */
    public function __construct(private WorkspaceService $service) {}

    // ==================== LIST ATTACHMENTS ====================

    /**
     * Retrieve a list of all attachments associated with a specific task.
     *
     * Returns a collection of task attachments with metadata including:
     * - File name and path
     * - MIME type and file size
     * - Uploader user ID
     * - Creation and update timestamps
     *
     * Authorization Requirements:
     * - User must be authenticated with valid Sanctum token
     * - User must be a member of the task's project workspace
     * - User must have permission to view task attachments
     *
     * Response includes:
     * - Attachment ID and task association
     * - File metadata (name, path, type, size)
     * - Uploader information
     * - Timestamps for audit trail
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @param  int  $taskId  The unique identifier of the task to retrieve attachments for
     * @return JsonResponse JSON response containing attachment collection and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If task is not found or user lacks permission
     */
    #[OA\Get(
        path: '/api/v1/tasks/{taskId}/attachments',
        summary: 'List all attachments for a task',
        description: 'Returns a list of file attachments linked to the specified task. The user must be authenticated and authorized to view the task.',
        security: [['sanctum' => []]],
        tags: ['Task Attachments'],
        parameters: [
            new OA\Parameter(
                name: 'taskId',
                description: 'The unique identifier of the task',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attachments retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/TaskAttachmentResource'),
                            description: 'Collection of task attachment resources'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Attachments retrieved successfully'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - User not authorized'),
            new OA\Response(response: 404, description: 'Task not found'),
        ]
    )]
    public function index(Request $request, int $taskId): JsonResponse
    {
        // Retrieve authenticated user from request
        // Throws UnauthorizedHttpException if no valid token provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Retrieve attachments from service layer
        // Service validates user has access to task's project
        $attachments = $this->service->getAttachmentsByTask($taskId);

        // Return formatted JSON response with attachment collection
        // Uses cached results from service layer (15 minute cache)
        return response()->json([
            'data' => TaskAttachmentResource::collection($attachments),
            'message' => __('workspaces.attachments_retrieved'),
        ]);
    }

    // ==================== UPLOAD ATTACHMENT ====================

    /**
     * Upload a new file attachment to the specified task.
     *
     * Handles file upload with validation and storage:
     * - Validates file is present and valid
     * - Stores file in 'task-attachments' directory
     * - Records file metadata (name, path, size, MIME type)
     * - Associates attachment with task and uploader
     * - Dispatches job for async processing (thumbnail, virus scan, etc.)
     * - Dispatches event for real-time notifications
     *
     * Supported File Types:
     * - Images: JPEG, PNG, GIF, WebP
     * - Documents: PDF
     *
     * Validation Rules:
     * - File is required
     * - Maximum file size: 10MB (10240 KB)
     * - Valid MIME type enforcement
     *
     * Security Considerations:
     * - Requires valid Sanctum authentication token
     * - Validates user membership in project workspace
     * - Checks upload permission on task
     * - Sanitizes file name and path
     *
     * @param  StoreTaskAttachmentRequest  $request  The validated request containing file upload
     * @param  int  $taskId  The unique identifier of the task to attach file to
     * @return JsonResponse JSON response containing created attachment resource and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If task is not found or user lacks permission
     * @throws \Illuminate\Validation\ValidationException If file validation fails
     */
    #[OA\Post(
        path: '/api/v1/tasks/{taskId}/attachments',
        summary: 'Upload a file attachment to a task',
        description: 'Uploads a file and links it to the specified task. Supported formats include images (jpeg, png) and PDFs. Maximum file size is enforced by validation rules.',
        security: [['sanctum' => []]],
        tags: ['Task Attachments'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Multipart form data containing the file',
            content: [
                'multipart/form-data' => new OA\MediaType(
                    schema: new OA\Schema(
                        required: ['file'],
                        properties: [
                            new OA\Property(
                                property: 'file',
                                type: 'string',
                                format: 'binary',
                                description: 'The file to upload (jpg, png, pdf supported)'
                            ),
                        ]
                    )
                ),
            ]
        ),
        parameters: [
            new OA\Parameter(
                name: 'taskId',
                description: 'The unique identifier of the task',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Attachment successfully uploaded',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/TaskAttachmentResource',
                            description: 'The newly created attachment resource'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Attachment uploaded successfully'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - User lacks permission to upload'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 422, description: 'Validation error (invalid file type, size, etc.)'),
            new OA\Response(response: 500, description: 'Server error during file storage'),
        ]
    )]
    public function store(StoreTaskAttachmentRequest $request, int $taskId): JsonResponse
    {
        // Retrieve authenticated user from request
        // Type hint helps IDE autocomplete and static analysis
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Retrieve uploaded file from request
        // Validation already performed by StoreTaskAttachmentRequest
        $file = $request->file('file');

        // Validate file upload was successful
        // Checks for upload errors and file integrity
        if (! $file->isValid()) {
            // Log file upload validation failure for debugging
            Log::channel('domain')->warning('Invalid file upload attempt', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $file->getErrorMessage(),
            ]);

            return response()->json(['error' => 'Invalid file upload'], 422);
        }

        // Store file in designated directory
        // Returns relative path from storage root or false on failure
        $storedPath = $file->store('task-attachments');

        // Handle storage failure
        if ($storedPath === false) {
            // Log storage failure for investigation
            Log::channel('domain')->error('Failed to store file attachment', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'file_name' => $file->getClientOriginalName(),
            ]);

            return response()->json(['error' => 'Failed to store file'], 500);
        }

        // Detect file MIME type from uploaded file
        // Falls back to generic octet-stream if detection fails
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        // Create attachment record via service layer
        // Service handles:
        // - File type validation (images, PDFs only)
        // - File size validation (max 10MB)
        // - Attachment entity creation
        // - Job dispatch for async processing
        // - Event dispatch for real-time notifications
        $attachment = $this->service->uploadAttachmentToTask(
            $taskId,
            $storedPath,
            $file->getClientOriginalName(),
            $mimeType,
            $file->getSize(),
            $user
        );

        // Return 201 Created with attachment resource and success message
        return response()->json([
            'data' => new TaskAttachmentResource($attachment),
            'message' => __('workspaces.attachment_uploaded'),
        ], 201);
    }

    // ==================== DELETE ATTACHMENT ====================

    /**
     * Permanently delete a task attachment.
     *
     * Removes attachment record and associated file from storage.
     * This action cannot be undone. Only the uploader can delete their own attachments.
     *
     * Deletion Process:
     * 1. Validate user is authenticated
     * 2. Verify user is attachment uploader (ownership check)
     * 3. Delete file from storage system
     * 4. Remove attachment record from database (soft delete)
     * 5. Log deletion for audit trail
     *
     * Authorization Requirements:
     * - User must be authenticated with valid token
     * - User must be the original attachment uploader
     * - User must have delete permission (enforced by policy)
     *
     * Security Considerations:
     * - Prevents deletion by non-owners
     * - Maintains audit trail via soft delete
     * - Removes physical file to free storage space
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @param  int  $taskId  The unique identifier of the task (for route consistency)
     * @param  int  $attachmentId  The unique identifier of the attachment to delete
     * @return JsonResponse JSON response with success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If attachment is not found or user is not owner
     */
    #[OA\Delete(
        path: '/api/v1/tasks/{taskId}/attachments/{attachmentId}',
        operationId: 'deleteAttachment',
        summary: 'Delete an attachment',
        description: 'Permanently delete a task attachment. Only the attachment uploader can delete their own attachments.',
        security: [['sanctum' => []]],
        tags: ['Task Attachments'],
        parameters: [
            new OA\Parameter(
                name: 'taskId',
                description: 'The unique identifier of the task',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'attachmentId',
                description: 'The unique identifier of the attachment to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attachment deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Attachment deleted successfully'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Not attachment owner'),
            new OA\Response(response: 404, description: 'Attachment not found'),
        ]
    )]
    public function destroy(Request $request, int $taskId, int $attachmentId): JsonResponse
    {
        // Retrieve authenticated user from request
        // Throws UnauthorizedHttpException if no valid token provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            // Delete attachment via service layer
            // Service validates:
            // 1. Attachment exists
            // 2. User is attachment uploader (ownership check)
            // 3. File is removed from storage
            // 4. Record is soft deleted for audit trail
            $this->service->deleteAttachment($attachmentId, $user->id);

            // Log successful deletion for audit trail
            Log::channel('domain')->info('Task attachment deleted', [
                'attachment_id' => $attachmentId,
                'task_id' => $taskId,
                'user_id' => $user->id,
            ]);

            // Return success message
            return response()->json(['message' => __('workspaces.attachment_deleted')]);
        } catch (\InvalidArgumentException $e) {
            // Log deletion failure for debugging
            Log::channel('domain')->warning('Attachment deletion failed', [
                'attachment_id' => $attachmentId,
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Return 403 Forbidden for authorization failures
            // This includes: not owner, attachment not found
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
