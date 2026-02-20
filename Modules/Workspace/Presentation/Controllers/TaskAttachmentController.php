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

#[OA\Tag(name: 'Task Attachments', description: 'Manage file attachments on tasks')]
class TaskAttachmentController extends Controller
{
    public function __construct(private WorkspaceService $service) {}

    #[OA\Get(path: '/tasks/{taskId}/attachments')]
    public function index(Request $request, int $taskId): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');
        $attachments = $this->service->getAttachmentsByTask($taskId);

        return response()->json([
            'data' => TaskAttachmentResource::collection($attachments),
            'message' => __('workspaces.attachments_retrieved'),
        ]);
    }

    #[OA\Post(
        path: '/tasks/{taskId}/attachments',
        summary: 'Upload an attachment to a task',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'The file to upload'
                        ),
                    ]
                )
            )
        ),
        tags: ['Task Attachments']
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
            $storedPath,               // string
            $file->getClientOriginalName(),
            $mimeType,                 // string (fallback)
            $file->getSize(),
            $user
        );

        return response()->json([
            'data' => new TaskAttachmentResource($attachment),
            'message' => __('workspaces.attachment_uploaded'),
        ], 201);
    }
}
