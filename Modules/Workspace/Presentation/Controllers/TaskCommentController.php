<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Presentation\Requests\StoreTaskCommentRequest;
use Modules\Workspace\Presentation\Requests\UpdateTaskCommentRequest;
use Modules\Workspace\Presentation\Resources\TaskCommentResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[OA\Tag(name: 'Task Comments', description: 'Manage comments on tasks')]
class TaskCommentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private WorkspaceService $service) {}

    #[OA\Get(path: '/tasks/{taskId}/comments')]
    public function index(Request $request, int $taskId): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');
        $comments = $this->service->getCommentsByTask($taskId, $user->id); // متد جدید در سرویس اضافه کنید

        return response()->json([
            'data' => TaskCommentResource::collection($comments),
            'message' => __('workspaces.comments_retrieved'),
        ]);
    }

    #[OA\Post(path: '/tasks/{taskId}/comments')]
    public function store(StoreTaskCommentRequest $request, int $taskId): JsonResponse
    {
        /** @var UserModel $user */
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        $task = $this->service->getTaskById($taskId);

        $this->authorize('comment', $task);

        $comment = $this->service->addCommentToTask($taskId, $request->input('comment'), $user);

        return response()->json([
            'data' => new TaskCommentResource($comment),
            'message' => __('workspaces.comment_added'),
        ], 201);
    }

    #[OA\Put(path: '/comments/{commentId}')]
    public function update(UpdateTaskCommentRequest $request, int $commentId): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        $comment = $this->service->updateComment(
            $commentId,
            $request->input('comment'),
            $user->id
        );

        return response()->json([
            'data' => new TaskCommentResource($comment),
            'message' => __('workspaces.comment_updated'),
        ]);
    }
}
