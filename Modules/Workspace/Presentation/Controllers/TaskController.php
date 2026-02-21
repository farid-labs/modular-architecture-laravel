<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Presentation\Requests\StoreTaskRequest;
use Modules\Workspace\Presentation\Requests\UpdateTaskRequest;
use Modules\Workspace\Presentation\Resources\TaskResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Controller responsible for managing tasks within projects.
 *
 * Provides endpoints to create, read, update, delete, and complete tasks.
 * All operations require authentication and proper project membership authorization.
 *
 * @see WorkspaceService For business logic implementation
 * @see TaskResource For API response formatting
 */
#[OA\Tag(name: 'Tasks', description: 'Manage tasks within projects')]
class TaskController extends Controller
{
    /**
     * Create a new TaskController instance.
     *
     * @param  WorkspaceService  $workspaceService  The workspace service dependency
     */
    public function __construct(private WorkspaceService $workspaceService) {}

    // ==================== LIST TASKS BY PROJECT ====================
    /**
     * Retrieve all tasks belonging to a specific project.
     *
     * Returns a collection of tasks with their current status and priority.
     * User must be a member of the project's workspace to view tasks.
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @param  int  $projectId  The project ID
     * @return JsonResponse JSON response with task collection
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     */
    #[OA\Get(
        path: '/projects/{projectId}/tasks',
        operationId: 'getTasksByProject',
        summary: 'List tasks in a project',
        description: 'Retrieve all tasks belonging to a specific project',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'projectId',
                in: 'path',
                required: true,
                description: 'The unique identifier of the project',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tasks retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/TaskResource')
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'Tasks retrieved successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Not a member of this project'),
            new OA\Response(response: 404, description: 'Project not found'),
        ]
    )]
    public function index(Request $request, int $projectId): JsonResponse
    {
        // Get the authenticated user
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        try {
            // Retrieve all tasks for the project
            $tasks = $this->workspaceService->getTasksByProject($projectId, $user->id);

            return response()->json([
                'data' => TaskResource::collection($tasks),
                'message' => __('workspaces.tasks_retrieved'),
            ]);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();

            // Handle project not found error
            if (str_contains($msg, 'Project not found') || str_contains($msg, 'project_not_found')) {
                return response()->json(['message' => __('workspaces.project_not_found', ['id' => $projectId])], 404);
            }

            // Handle user not being a member of the project
            if (str_contains($msg, 'not a member')) {
                return response()->json(['message' => __('workspaces.not_member_of_project')], 403);
            }

            // Return other errors
            return response()->json(['message' => $msg], 422);
        }
    }

    // ==================== CREATE TASK ====================
    /**
     * Create a new task within a specific project.
     *
     * Creates a task with title, description, priority, and optional due date.
     * User must be a member of the project's workspace to create tasks.
     *
     * @param  StoreTaskRequest  $request  The validated request containing task data
     * @param  int  $projectId  The project ID
     * @return JsonResponse JSON response with created task
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     */
    #[OA\Post(
        path: '/projects/{projectId}/tasks',
        operationId: 'createTask',
        summary: 'Create a new task',
        description: 'Create a new task within a specific project',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'projectId',
                in: 'path',
                required: true,
                description: 'The unique identifier of the project',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Implement login'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Task description'),
                    new OA\Property(property: 'assigned_to', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], example: 'high'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Task created successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource'),
                        new OA\Property(property: 'message', type: 'string', example: 'Task created successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Not a member of this project'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreTaskRequest $request, int $projectId): JsonResponse
    {
        // Get the authenticated user
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        try {
            // Convert validated request data into a TaskDTO
            $taskDTO = TaskDTO::fromArray([
                ...$request->validated(),
                'project_id' => $projectId,
            ]);

            // Create the task using the service
            $task = $this->workspaceService->createTask($taskDTO, $user);

            return response()->json([
                'data' => new TaskResource($task),
                'message' => __('workspaces.task_created'),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();

            // Handle project not found error
            if (str_contains($msg, 'Project not found')) {
                return response()->json(['message' => __('workspaces.project_not_found', ['id' => $projectId])], 404);
            }

            // Handle user not being a member of the project
            if (str_contains($msg, 'not a member')) {
                return response()->json(['message' => __('workspaces.not_member_of_project')], 403);
            }

            // Return validation or other task creation error
            return response()->json(['message' => $msg], 422);
        }
    }

    // ==================== GET TASK BY ID ====================
    /**
     * Retrieve a single task by its unique identifier.
     *
     * Returns detailed task information including status, priority, and due date.
     * User must have access to the task's project.
     *
     * @param  int  $id  The task ID
     * @return JsonResponse JSON response with task details
     *
     * @throws InvalidArgumentException If task is not found
     */
    #[OA\Get(
        path: '/tasks/{id}',
        operationId: 'getTaskById',
        summary: 'Get task by ID',
        description: 'Retrieve a single task by its unique identifier',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The unique identifier of the task',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource'),
                        new OA\Property(property: 'message', type: 'string', example: 'Task retrieved successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        try {
            // Retrieve task details by ID
            $task = $this->workspaceService->getTaskById($id);

            return response()->json([
                'data' => new TaskResource($task),
                'message' => __('workspaces.task_retrieved'),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle task not found
            if (str_contains($e->getMessage(), 'Task not found')) {
                return response()->json(['message' => __('workspaces.task_not_found', ['id' => $id])], 404);
            }

            // Return other task retrieval errors
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ==================== COMPLETE TASK ====================
    /**
     * Mark a task as completed.
     *
     * Updates task status to COMPLETED using immutable entity pattern.
     * User must be a member of the project to complete tasks.
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @param  int  $id  The task ID
     * @return JsonResponse JSON response with completed task
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     */
    #[OA\Put(
        path: '/tasks/{id}/complete',
        operationId: 'completeTask',
        summary: 'Mark task as completed',
        description: 'Update task status to completed',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The unique identifier of the task',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task completed successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource'),
                        new OA\Property(property: 'message', type: 'string', example: 'Task completed successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found'),
        ]
    )]
    public function complete(Request $request, int $id): JsonResponse
    {
        // Get the authenticated user
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        try {
            // Mark the task as completed
            $task = $this->workspaceService->completeTask($id, $user);

            return response()->json([
                'data' => new TaskResource($task),
                'message' => __('workspaces.task_completed'),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle task not found
            if (str_contains($e->getMessage(), 'Task not found')) {
                return response()->json(['message' => __('workspaces.task_not_found', ['id' => $id])], 404);
            }

            // Return other task completion errors
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ==================== UPDATE TASK ====================
    /**
     * Update an existing task with new data.
     *
     * Supports partial updates - only provided fields will be updated.
     * User must be a member of the project to update tasks.
     *
     * @param  UpdateTaskRequest  $request  The validated request containing update data
     * @param  int  $id  The task ID
     * @return JsonResponse JSON response with updated task
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     */
    #[OA\Put(
        path: '/tasks/{id}',
        operationId: 'updateTask',
        summary: 'Update task',
        description: 'Update an existing task with new data',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The unique identifier of the task',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Updated title'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Updated description'),
                    new OA\Property(property: 'assigned_to', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed', 'blocked', 'cancelled'], nullable: true, example: 'in_progress'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], nullable: true, example: 'high'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task updated successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource'),
                        new OA\Property(property: 'message', type: 'string', example: 'Task updated successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            // Filter out null values for partial update
            $validatedData = array_filter($request->validated(), fn ($value) => $value !== null);

            // Check if there are any fields to update
            if (empty($validatedData)) {
                return response()->json(['message' => __('workspaces.no_fields_to_update')], 400);
            }

            // Get project_id from existing task to maintain referential integrity
            $taskDTO = TaskDTO::fromArray([...$validatedData, 'project_id' => $this->workspaceService->getTaskById($id)->getProjectId()]);

            // Update the task
            $task = $this->workspaceService->updateTask($id, $taskDTO, $user);

            return response()->json([
                'data' => new TaskResource($task),
                'message' => __('workspaces.task_updated'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    // ==================== DELETE TASK ====================
    /**
     * Permanently delete a task.
     *
     * This action cannot be undone. All associated comments and attachments
     * will be cascade deleted. User must be a member of the project.
     *
     * @param  int  $id  The task ID
     * @return JsonResponse JSON response with success message
     *
     * @throws InvalidArgumentException If task is not found
     */
    #[OA\Delete(
        path: '/tasks/{id}',
        operationId: 'deleteTask',
        summary: 'Delete task',
        description: 'Permanently delete a task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The unique identifier of the task',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task deleted successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Task deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Task not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            // Delete the task
            $this->workspaceService->deleteTask($id);

            return response()->json(['message' => __('workspaces.task_deleted')]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
