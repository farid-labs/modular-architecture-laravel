<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Presentation\Requests\StoreTaskAttachmentRequest;
use Modules\Workspace\Presentation\Resources\TaskAttachmentResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Controller responsible for managing task attachments.
 *
 * Provides endpoints to list and upload file attachments for tasks.
 * All operations require authentication and proper authorization.
 */
#[OA\Tag(name: 'Task Attachments', description: 'Endpoints for managing file attachments on tasks')]
class TaskAttachmentController extends Controller
{
    public function __construct(private WorkspaceService $service) {}

    /**
     * Retrieve a list of all attachments associated with a specific task.
     *
     * Returns a collection of task attachments with metadata.
     * Requires the authenticated user to have access to the task's project.
     */
    #[OA\Get(
        path: '/tasks/{taskId}/attachments',
        summary: 'List all attachments for a task',
        description: 'Returns a list of file attachments linked to the specified task. '.
            'The user must be authenticated and authorized to view the task.',
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
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');
        $attachments = $this->service->getAttachmentsByTask($taskId);

        return response()->json([
            'data' => TaskAttachmentResource::collection($attachments),
            'message' => __('workspaces.attachments_retrieved'),
        ]);
    }

    /**
     * Upload a new file attachment to the specified task.
     *
     * Supports common file types (images, PDFs, etc.) with size validation.
     * The file is stored and associated with the task.
     * Requires authentication and upload permission on the task.
     */
    #[OA\Post(
        path: '/tasks/{taskId}/attachments',
        summary: 'Upload a file attachment to a task',
        description: 'Uploads a file and links it to the specified task. '.
            'Supported formats include images (jpeg, png) and PDFs. '.
            'Maximum file size is enforced by validation rules.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Multipart form data containing the file',
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
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
            )
        ),
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
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');
        $file = $request->file('file');

        if (! $file->isValid()) {
            return response()->json(['error' => 'Invalid file upload'], 422);
        }

        $storedPath = $file->store('task-attachments');
        if ($storedPath === false) {
            return response()->json(['error' => 'Failed to store file'], 500);
        }

        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        $attachment = $this->service->uploadAttachmentToTask(
            $taskId,
            $storedPath,
            $file->getClientOriginalName(),
            $mimeType,
            $file->getSize(),
            $user
        );

        return response()->json([
            'data' => new TaskAttachmentResource($attachment),
            'message' => __('workspaces.attachment_uploaded'),
        ], 201);
    }

    // ==================== DELETE ATTACHMENT ====================
    #[OA\Delete(
        path: '/tasks/{taskId}/attachments/{attachmentId}',
        operationId: 'deleteAttachment',
        summary: 'Delete an attachment',
        security: [['sanctum' => []]],
        tags: ['Task Attachments'],
        parameters: [
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'attachmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Attachment deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Not attachment owner'),
            new OA\Response(response: 404, description: 'Attachment not found'),
        ]
    )]
    public function destroy(Request $request, int $taskId, int $attachmentId): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            $this->service->deleteAttachment($attachmentId, $user->id);

            return response()->json(['message' => __('workspaces.attachment_deleted')]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
