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
 * Provides RESTful endpoints to list, create, update, and delete task comments.
 * All operations require authentication via Sanctum token and proper authorization.
 * Comments support collaboration features with ownership validation and time-limited editing.
 *
 * Key Features:
 * - List all comments for a specific task (ordered by creation date, newest first)
 * - Create new comments with validation (3-2000 characters)
 * - Update existing comments (author only, within 30-minute window)
 * - Delete comments (author only)
 * - Automatic event dispatching for real-time notifications
 *
 * @see WorkspaceService For business logic implementation
 * @see TaskCommentResource For API response formatting
 * @see StoreTaskCommentRequest For comment creation validation
 * @see UpdateTaskCommentRequest For comment update validation
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
#[OA\Tag(name: 'Task Comments', description: 'Endpoints for managing comments on tasks')]
class TaskCommentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Create a new TaskCommentController instance.
     *
     * @param  WorkspaceService  $service  The workspace service dependency for comment operations
     */
    public function __construct(private WorkspaceService $service) {}

    // ==================== LIST COMMENTS ====================

    /**
     * Retrieve a list of all comments associated with a specific task.
     *
     * Returns a paginated collection of task comments including author information,
     * timestamps, and comment content. Comments are ordered by creation date
     * (newest first) to show recent discussions prominently.
     *
     * Authorization Requirements:
     * - User must be authenticated with valid Sanctum token
     * - User must be a member of the task's project workspace
     * - User must have permission to view task comments
     *
     * Response includes:
     * - Comment ID and content
     * - Author user ID
     * - Creation and update timestamps
     * - Task association
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @param  int  $taskId  The unique identifier of the task to retrieve comments for
     * @return JsonResponse JSON response containing comment collection and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If task is not found or user lacks permission
     *
     * @OA\Get(
     *     path="/api/v1/tasks/{taskId}/comments",
     *     summary="List all comments for a task",
     *     description="Returns a list of comments linked to the specified task. The authenticated user must have access to the task's project. Comments are ordered by creation date (newest first).",
     *     security={{"sanctum": {}}},
     *     tags={"Task Comments"},
     *
     *     @OA\Parameter(
     *         name="taskId",
     *         description="The unique identifier of the task",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Comments retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/TaskCommentResource"),
     *                 description="Collection of task comment resources"
     *             ),
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Comments retrieved successfully"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - Missing or invalid authentication token"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User does not have permission to view comments"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Task not found"
     *     )
     * )
     */
    public function index(Request $request, int $taskId): JsonResponse
    {
        // Retrieve authenticated user from request
        // Throws UnauthorizedHttpException if no valid token provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Retrieve comments from service layer with authorization check
        // Service validates user is member of task's project workspace
        $comments = $this->service->getCommentsByTask($taskId, $user->id);

        // Return formatted JSON response with comment collection
        return response()->json([
            'data' => TaskCommentResource::collection($comments),
            'message' => __('workspaces.comments_retrieved'),
        ]);
    }

    // ==================== CREATE COMMENT ====================

    /**
     * Add a new comment to the specified task.
     *
     * Creates a comment associated with the authenticated user and the given task.
     * The comment is immediately visible to all workspace members with task access.
     * A domain event (TaskCommentAdded) is dispatched for real-time notifications.
     *
     * Validation Rules:
     * - Comment text is required
     * - Minimum length: 3 characters
     * - Maximum length: 2000 characters
     * - User must be authenticated
     * - User must be member of task's project workspace
     * - User must have 'comment' policy permission
     *
     * Security:
     * - Requires valid Sanctum authentication token
     * - Validates user membership in project workspace
     * - Checks policy authorization before creation
     *
     * @param  StoreTaskCommentRequest  $request  The validated request containing comment text
     * @param  int  $taskId  The unique identifier of the task to comment on
     * @return JsonResponse JSON response containing created comment resource and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \Illuminate\Auth\Access\AuthorizationException If user lacks comment permission
     * @throws \InvalidArgumentException If task is not found or validation fails
     *
     * @OA\Post(
     *     path="/api/v1/tasks/{taskId}/comments",
     *     summary="Create a new comment on a task",
     *     description="Adds a comment to the specified task. The authenticated user must be a member of the task's project. Comment content must meet minimum length requirements.",
     *     security={{"sanctum": {}}},
     *     tags={"Task Comments"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"comment"},
     *
     *             @OA\Property(
     *                 property="comment",
     *                 type="string",
     *                 description="The comment text",
     *                 minLength=3,
     *                 maxLength=2000,
     *                 example="This is a professional comment for discussion."
     *             )
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="taskId",
     *         description="The unique identifier of the task",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Comment created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/TaskCommentResource",
     *                 description="The newly created comment resource"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Comment added successfully"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - User lacks permission to comment"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=422, description="Validation error (e.g., comment too short)")
     * )
     */
    public function store(StoreTaskCommentRequest $request, int $taskId): JsonResponse
    {
        // Retrieve authenticated user from request
        // Type hint helps IDE autocomplete and static analysis
        /** @var UserModel $user */
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Retrieve task to verify it exists before authorization check
        // This ensures we're authorizing against a valid task entity
        $task = $this->service->getTaskById($taskId);

        // Authorize user has permission to comment on this task
        // Uses Laravel Policy system (TaskPolicy@comment)
        // Validates user is member of task's project workspace
        $this->authorize('comment', $task);

        // Create comment via service layer
        // Service handles validation, persistence, and event dispatching
        $comment = $this->service->addCommentToTask(
            $taskId,
            $request->input('comment'),
            $user
        );

        // Return 201 Created with comment resource and success message
        return response()->json([
            'data' => new TaskCommentResource($comment),
            'message' => __('workspaces.comment_added'),
        ], 201);
    }

    // ==================== UPDATE COMMENT ====================

    /**
     * Update an existing comment.
     *
     * Allows the comment author to edit their comment within a limited time window.
     * This feature supports correcting typos or adding additional information
     * shortly after posting.
     *
     * Authorization Requirements:
     * - User must be the original comment author
     * - Update must occur within 30 minutes of comment creation
     * - User must be authenticated with valid token
     *
     * Validation Rules:
     * - Comment text is required
     * - Minimum length: 3 characters
     * - Maximum length: 2000 characters
     * - Time window: 30 minutes from creation
     *
     * Security Considerations:
     * - Prevents unauthorized edits by other users
     * - Time limit prevents editing old discussions
     * - Maintains conversation integrity
     *
     * @param  UpdateTaskCommentRequest  $request  The validated request containing updated comment text
     * @param  int  $commentId  The unique identifier of the comment to update
     * @return JsonResponse JSON response containing updated comment resource and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If comment is not found, user is not author, or time window expired
     *
     * @OA\Put(
     *     path="/api/v1/comments/{commentId}",
     *     summary="Update an existing comment",
     *     description="Updates the content of a comment. Only the comment author can edit, and only within the allowed time frame (e.g., 30 minutes).",
     *     security={{"sanctum": {}}},
     *     tags={"Task Comments"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"comment"},
     *
     *             @OA\Property(
     *                 property="comment",
     *                 type="string",
     *                 description="The updated comment text",
     *                 minLength=3,
     *                 maxLength=2000,
     *                 example="Updated comment with additional details."
     *             )
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="commentId",
     *         description="The unique identifier of the comment to update",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/TaskCommentResource",
     *                 description="The updated comment resource"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Comment updated successfully"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Not the comment owner or edit time expired"),
     *     @OA\Response(response=404, description="Comment not found"),
     *     @OA\Response(response=422, description="Validation error (e.g., comment too short)")
     * )
     */
    public function update(UpdateTaskCommentRequest $request, int $commentId): JsonResponse
    {
        // Retrieve authenticated user from request
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Update comment via service layer
        // Service validates:
        // 1. User is comment author
        // 2. Update is within 30-minute window
        // 3. Comment content meets validation rules
        $comment = $this->service->updateComment(
            $commentId,
            $request->input('comment'),
            $user->id
        );

        // Return updated comment resource with success message
        return response()->json([
            'data' => new TaskCommentResource($comment),
            'message' => __('workspaces.comment_updated'),
        ]);
    }

    // ==================== DELETE COMMENT ====================

    /**
     * Permanently delete a comment.
     *
     * Removes a comment from the system. This action is restricted to the
     * original comment author to prevent unauthorized deletion of discussions.
     * The comment is soft-deleted, allowing potential recovery if needed.
     *
     * Authorization Requirements:
     * - User must be the original comment author
     * - User must be authenticated with valid token
     *
     * Security Considerations:
     * - Prevents deletion by other users
     * - Maintains conversation history integrity
     * - Soft delete allows audit trail preservation
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @param  int  $taskId  The unique identifier of the task (for route consistency)
     * @param  int  $commentId  The unique identifier of the comment to delete
     * @return JsonResponse JSON response with success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If comment is not found or user is not author
     *
     * @OA\Delete(
     *     path="/api/v1/tasks/{taskId}/comments/{commentId}",
     *     operationId="deleteComment",
     *     summary="Delete a comment",
     *     description="Permanently delete a comment. Only the comment author can delete their own comments.",
     *     security={{"sanctum": {}}},
     *     tags={"Task Comments"},
     *
     *     @OA\Parameter(
     *         name="taskId",
     *         description="The unique identifier of the task",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="commentId",
     *         description="The unique identifier of the comment to delete",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Comment deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Comment deleted successfully"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Not comment owner"),
     *     @OA\Response(response=404, description="Comment not found")
     * )
     */
    public function destroy(Request $request, int $taskId, int $commentId): JsonResponse
    {
        // Retrieve authenticated user from request
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            // Delete comment via service layer
            // Service validates user is comment author before deletion
            // Performs soft delete to maintain audit trail
            $this->service->deleteComment($commentId, $user->id);

            // Return success message
            return response()->json(['message' => __('workspaces.comment_deleted')]);
        } catch (\InvalidArgumentException $e) {
            // Return 403 Forbidden for authorization failures
            // This includes: not author, comment not found
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
