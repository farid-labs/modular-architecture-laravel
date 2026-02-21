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

/**
 * Controller responsible for managing comments on tasks.
 *
 * Provides endpoints to list, create, and update task comments.
 * All operations require authentication and proper authorization.
 */
#[OA\Tag(name: 'Task Comments', description: 'Endpoints for managing comments on tasks')]
class TaskCommentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private WorkspaceService $service) {}

    /**
     * Retrieve a list of all comments associated with a specific task.
     *
     * Returns a collection of task comments including author information and timestamps.
     * Requires the authenticated user to be a member of the task's project.
     */
    #[OA\Get(
        path: '/tasks/{taskId}/comments',
        summary: 'List all comments for a task',
        description: 'Returns a list of comments linked to the specified task. '.
            'The authenticated user must have access to the task’s project. '.
            'Comments are ordered by creation date (newest first).',
        security: [['sanctum' => []]],
        tags: ['Task Comments'],
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
                description: 'Comments retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/TaskCommentResource'),
                            description: 'Collection of task comment resources'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Comments retrieved successfully'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated - Missing or invalid authentication token'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User does not have permission to view comments'
            ),
            new OA\Response(
                response: 404,
                description: 'Task not found'
            ),
        ]
    )]
    public function index(Request $request, int $taskId): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');
        $comments = $this->service->getCommentsByTask($taskId, $user->id);

        return response()->json([
            'data' => TaskCommentResource::collection($comments),
            'message' => __('workspaces.comments_retrieved'),
        ]);
    }

    /**
     * Add a new comment to the specified task.
     *
     * Creates a comment associated with the authenticated user and the given task.
     * Requires the user to have permission to comment on the task.
     */
    #[OA\Post(
        path: '/tasks/{taskId}/comments',
        summary: 'Create a new comment on a task',
        description: 'Adds a comment to the specified task. '.
            'The authenticated user must be a member of the task’s project. '.
            'Comment content must meet minimum length requirements.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['comment'],
                properties: [
                    new OA\Property(
                        property: 'comment',
                        type: 'string',
                        description: 'The comment text',
                        minLength: 3,
                        maxLength: 2000,
                        example: 'This is a professional comment for discussion.'
                    ),
                ]
            )
        ),
        tags: ['Task Comments'],
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
                description: 'Comment created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/TaskCommentResource',
                            description: 'The newly created comment resource'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Comment added successfully'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - User lacks permission to comment'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 422, description: 'Validation error (e.g., comment too short)'),
        ]
    )]
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

    /**
     * Update an existing comment.
     *
     * Allows the comment author to edit their comment within a limited time window.
     * Requires ownership verification.
     */
    #[OA\Put(
        path: '/comments/{commentId}',
        summary: 'Update an existing comment',
        description: 'Updates the content of a comment. '.
            'Only the comment author can edit, and only within the allowed time frame (e.g., 30 minutes).',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['comment'],
                properties: [
                    new OA\Property(
                        property: 'comment',
                        type: 'string',
                        description: 'The updated comment text',
                        minLength: 3,
                        maxLength: 2000,
                        example: 'Updated comment with additional details.'
                    ),
                ]
            )
        ),
        tags: ['Task Comments'],
        parameters: [
            new OA\Parameter(
                name: 'commentId',
                description: 'The unique identifier of the comment to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comment updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/TaskCommentResource',
                            description: 'The updated comment resource'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Comment updated successfully'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Not the comment owner or edit time expired'),
            new OA\Response(response: 404, description: 'Comment not found'),
            new OA\Response(response: 422, description: 'Validation error (e.g., comment too short)'),
        ]
    )]
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

    // ==================== DELETE COMMENT ====================
    #[OA\Delete(
        path: '/tasks/{taskId}/comments/{commentId}',
        operationId: 'deleteComment',
        summary: 'Delete a comment',
        security: [['sanctum' => []]],
        tags: ['Task Comments'],
        parameters: [
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'commentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comment deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Not comment owner'),
            new OA\Response(response: 404, description: 'Comment not found'),
        ]
    )]
    public function destroy(Request $request, int $taskId, int $commentId): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            $this->service->deleteComment($commentId, $user->id);

            return response()->json(['message' => __('workspaces.comment_deleted')]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
