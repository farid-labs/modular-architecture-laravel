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

    #[OA\Post(path: '/tasks/{taskId}/attachments', consumes: ['multipart/form-data'])]
    public function store(StoreTaskAttachmentRequest $request, int $taskId): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');
        $file = $request->file('file');

        $attachment = $this->service->uploadAttachmentToTask(
            $taskId,
            $file->store('task-attachments'),
            $file->getClientOriginalName(),
            $file->getMimeType(),
            $file->getSize(),
            $user
        );

        return response()->json([
            'data' => new TaskAttachmentResource($attachment),
            'message' => __('workspaces.attachment_uploaded'),
        ], 201);
    }
}
