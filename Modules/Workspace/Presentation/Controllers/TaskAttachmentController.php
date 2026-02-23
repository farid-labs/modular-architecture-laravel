<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Domain\Exceptions\AuthorizationException;
use Modules\Workspace\Domain\ValueObjects\AttachmentCollection;
use Modules\Workspace\Presentation\Requests\StoreTaskAttachmentRequest;
use Modules\Workspace\Presentation\Resources\TaskAttachmentResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

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
     * @throws AuthorizationException If task is not found or user lacks permission
     */
    #[OA\Get(
        path: '/tasks/{taskId}/attachments',
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
        // Retrieve authenticated user from request context
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            // CRITICAL FIX: Pass user ID to service for authorization check
            $attachments = $this->service->getAttachmentsByTask($taskId, $user->id);

            return response()->json([
                'data' => TaskAttachmentResource::collection($attachments),
                'message' => __('workspaces.attachments_retrieved'),
            ]);
        } catch (AuthorizationException $e) {
            return match ($e->errorCode) {
                'task_not_found' => response()->json(['message' => $e->getMessage()], 404),
                'not_member_of_project' => response()->json(['message' => $e->getMessage()], 403),
                default => response()->json(['message' => $e->getMessage()], 422),
            };
        }
    }

    // ==================== UPLOAD ATTACHMENT ====================

    /**
     * Upload multiple file attachments to the specified task.
     *
     * Handles multiple file upload with comprehensive validation:
     * - Validates file count (1-3 files per request)
     * - Validates each file (max 10MB, allowed types)
     * - Stores files in 'task-attachments' directory
     * - Records file metadata using domain value objects
     * - Associates attachments with task and uploader
     * - Dispatches jobs for async processing (thumbnail, virus scan)
     * - Dispatches events for real-time notifications
     * - Invalidates cache for data consistency
     *
     * Domain Rules:
     * - AttachmentCollection enforces max 3 files rule
     * - FileSize value object enforces 10MB limit
     * - AttachmentUpload validates each file individually
     *
     * Security Considerations:
     * - Requires valid Sanctum authentication token
     * - Validates user membership in project workspace
     * - Checks upload permission on task
     * - Sanitizes file names and paths
     *
     * @param  StoreTaskAttachmentRequest  $request  The validated request containing file uploads
     * @param  int  $taskId  The unique identifier of the task
     * @return JsonResponse JSON response containing created attachments and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     */
    #[OA\Post(
        path: '/tasks/{taskId}/attachments',
        operationId: 'uploadTaskAttachments',
        summary: 'Upload multiple file attachments to a task',
        description: 'Uploads 1-3 files and links them to the specified task. Maximum 10MB per file. Supported formats: JPEG, PNG, GIF, WebP, PDF.',
        security: [['sanctum' => []]],
        tags: ['Task Attachments'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Multipart form data containing files',
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['files'],
                    properties: [
                        new OA\Property(
                            property: 'files',
                            type: 'array',
                            minItems: 1,
                            maxItems: 3,
                            items: new OA\Items(
                                type: 'string',
                                format: 'binary',
                                description: 'File to upload (max 10MB each)'
                            ),
                            description: 'Array of 1-3 files to upload'
                        ),
                    ]
                )
            )
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
                description: 'Attachments successfully uploaded',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/TaskAttachmentResource'),
                            description: 'Array of created attachment resources'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: '3 attachments uploaded successfully'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - User lacks permission'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function store(StoreTaskAttachmentRequest $request, int $taskId): JsonResponse
    {
        // Authenticate User
        // Retrieve authenticated user from request context
        // Throws UnauthorizedHttpException if no valid token provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Validate Task Existence (Domain Rule)
        // Ensure the parent task exists before proceeding with file uploads
        // This prevents orphaned attachments and provides clear error messaging
        try {
            $task = $this->service->getTaskById($taskId);
        } catch (AuthorizationException $e) {
            Log::channel('domain')->warning('Task not found for attachment upload', [
                'task_id' => $taskId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => __('workspaces.task_not_found', ['id' => $taskId]),
            ], 404);
        }

        // Create Domain Value Object for File Collection
        // AttachmentCollection enforces domain rules:
        // - Minimum 1 file, maximum 3 files per request
        // - Each file validated as AttachmentUpload value object
        // - FileSize value object enforces 10MB limit per file
        try {
            $attachmentCollection = new AttachmentCollection($request->getFiles());
        } catch (AuthorizationException $e) {
            Log::channel('domain')->warning('Attachment collection validation failed', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        // Execute Upload via Service Layer
        // Delegate to service layer which handles:
        // - File storage in designated directory
        // - Attachment entity creation with value objects
        // - Job dispatch for async processing
        // - Event dispatch for real-time notifications
        // - Cache invalidation for data consistency
        try {
            $attachments = $this->service->uploadAttachmentsToTask(
                $taskId,
                $attachmentCollection,
                $user
            );

            // Invalidate attachment cache to ensure data consistency
            // Ensures subsequent GET requests return updated attachment list
            Cache::forget("task:{$taskId}:attachments");

            // Audit log: Record successful upload for compliance
            Log::channel('domain')->info('Task attachments uploaded successfully', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'attachment_count' => $attachmentCollection->count(),
                'total_size' => $attachmentCollection->totalSize()->formatted(),
            ]);

            // Return 201 Created with attachment resources
            return response()->json([
                'data' => TaskAttachmentResource::collection($attachments),
                'message' => __(
                    'workspaces.attachments_uploaded',
                    ['count' => $attachmentCollection->count()]
                ),
            ], 201);
        } catch (AuthorizationException $e) {
            // Handle business logic validation errors
            Log::channel('domain')->warning('Attachment upload validation failed', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            // Handle unexpected server errors
            Log::channel('domain')->error('Unexpected error during attachment upload', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'message' => __('workspaces.attachment_upload_failed'),
            ], 500);
        }
    }

    // ==================== DELETE ATTACHMENT ====================

    /**
     * Delete a task attachment.
     *
     * Permanently removes an attachment from a task after performing comprehensive validation:
     * - Task existence verification
     * - Attachment existence and task relationship integrity
     * - User authorization (only uploader can delete)
     * - Cache invalidation for data consistency
     *
     * This endpoint implements defense-in-depth security by validating the attachment-task
     * relationship to prevent ID manipulation attacks. All operations are logged for
     * audit trail and compliance requirements.
     *
     * Authorization Requirements:
     * - User must be authenticated with valid Sanctum token
     * - User must be the original uploader of the attachment
     * - Task must exist and be accessible
     *
     * Security Considerations:
     * - Prevents unauthorized deletion through ownership validation
     * - Validates attachment-task relationship to prevent ID tampering
     * - Logs all deletion attempts for security monitoring
     * - Invalidates cache to prevent stale data exposure
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @param  int  $taskId  The unique identifier of the parent task
     * @param  int  $attachmentId  The unique identifier of the attachment to delete
     * @return JsonResponse JSON response with success or error message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     */
    #[OA\Delete(
        path: '/tasks/{taskId}/attachments/{attachmentId}',
        operationId: 'deleteTaskAttachment',
        summary: 'Delete a task attachment',
        description: 'Permanently delete an attachment from a task. Only the uploader can delete their own attachments.',
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
            new OA\Parameter(
                name: 'attachmentId',
                description: 'The unique identifier of the attachment',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
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
            new OA\Response(response: 403, description: 'Forbidden - Not attachment uploader'),
            new OA\Response(response: 404, description: 'Task or attachment not found'),
        ]
    )]
    public function destroy(Request $request, int $taskId, int $attachmentId): JsonResponse
    {
        // Retrieve authenticated user from request context
        // Throws UnauthorizedHttpException if no valid authentication token is provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Validate Task Existence
        // Ensure the parent task exists before proceeding with attachment operations.
        // This prevents orphaned attachment references and provides clear error messaging.
        // Early validation reduces unnecessary database queries and improves performance.
        try {
            $task = $this->service->getTaskById($taskId);
        } catch (AuthorizationException $e) {
            // Log warning for security monitoring and debugging
            Log::channel('domain')->warning('Task not found for attachment deletion', [
                'task_id' => $taskId,
                'user_id' => $user->id,
            ]);

            // Return 404 with localized error message
            return response()->json([
                'message' => __('workspaces.task_not_found', ['id' => $taskId]),
            ], 404);
        }

        // Validate Attachment Existence and Task Ownership
        // Fetch the specific attachment by ID and verify it belongs to the specified task.
        // This security check prevents ID manipulation attacks where users might attempt
        // to delete attachments from tasks they don't have access to.
        try {
            $attachment = $this->service->getAttachmentById($attachmentId);

            // Security check: Ensure attachment-task relationship integrity
            // Mismatch indicates potential ID tampering or malformed request
            if ($attachment->getTaskId() !== $taskId) {
                Log::channel('domain')->warning('Attachment does not belong to specified task', [
                    'attachment_id' => $attachmentId,
                    'task_id' => $taskId,
                    'actual_task_id' => $attachment->getTaskId(),
                    'user_id' => $user->id,
                ]);

                // Return 404 instead of 403 to avoid revealing attachment existence
                return response()->json([
                    'message' => __('workspaces.attachment_not_found', ['id' => $attachmentId]),
                ], 404);
            }
        } catch (AuthorizationException $e) {
            // Attachment not found - log for monitoring
            Log::channel('domain')->warning('Attachment not found for deletion', [
                'attachment_id' => $attachmentId,
                'task_id' => $taskId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => __('workspaces.attachment_not_found', ['id' => $attachmentId]),
            ], 404);
        }

        // Authorize User (Uploader Ownership Validation)
        // Enforce business rule: Only the original uploader can delete attachments.
        // This prevents unauthorized deletion and maintains audit trail integrity.
        // Comparison is done on user IDs to avoid unnecessary database queries.
        if ($attachment->getUploadedBy() !== $user->id) {
            // Log authorization failure for security audit
            Log::channel('domain')->warning('User not authorized to delete attachment', [
                'attachment_id' => $attachmentId,
                'task_id' => $taskId,
                'user_id' => $user->id,
                'uploader_id' => $attachment->getUploadedBy(),
            ]);

            // Return 403 Forbidden with localized error message
            return response()->json([
                'message' => __('workspaces.attachment_delete_forbidden'),
            ], 403);
        }

        // Execute Attachment Deletion
        // Delegate deletion to service layer which handles:
        // - Physical file removal from storage
        // - Database record soft-delete (audit trail preservation)
        // - Domain event dispatching for real-time notifications
        try {
            $this->service->deleteAttachment($attachmentId, $user->id);

            // Invalidate cache to prevent stale data exposure
            // Ensures subsequent GET requests return updated attachment list
            Cache::forget("task:{$taskId}:attachments");

            // Audit log: Record successful deletion for compliance and debugging
            Log::channel('domain')->info('Task attachment deleted', [
                'attachment_id' => $attachmentId,
                'task_id' => $taskId,
                'user_id' => $user->id,
            ]);

            // Return 200 OK with success message
            return response()->json([
                'message' => __('workspaces.attachment_deleted'),
            ]);
        } catch (AuthorizationException $e) {
            // Error handling: Log deletion failure for investigation
            Log::channel('domain')->warning('Attachment deletion failed', [
                'attachment_id' => $attachmentId,
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Return 403 with error message (could be ownership or storage issue)
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
