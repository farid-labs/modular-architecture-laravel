<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;                    // ← Correct import
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Presentation\Requests\StoreTaskRequest;
use Modules\Workspace\Presentation\Requests\UpdateTaskRequest;
use Modules\Workspace\Presentation\Resources\TaskResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[OA\Tag(name: 'Tasks', description: 'Manage tasks within projects')]
class TaskController extends Controller
{
    // Inject WorkspaceService into the controller
    public function __construct(private WorkspaceService $workspaceService) {}

    // ==================== CREATE TASK ====================
    #[OA\Post(
        path: '/projects/{projectId}/tasks',
        operationId: 'createTask',
        summary: 'Create a new task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Implement login'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'assigned_to', type: 'integer', nullable: true),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], example: 'high'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Task created successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
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
    #[OA\Get(
        path: '/tasks/{id}',
        operationId: 'getTask',
        summary: 'Get task by ID',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
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
    #[OA\Put(
        path: '/tasks/{id}/complete',
        operationId: 'completeTask',
        summary: 'Mark task as completed',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task completed successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
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
    #[OA\Put(
        path: '/tasks/{id}',
        operationId: 'updateTask',
        summary: 'Update task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'assigned_to', type: 'integer', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed', 'blocked', 'cancelled'], nullable: true),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], nullable: true),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Task updated successfully'),
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
            $validatedData = array_filter($request->validated(), fn ($value) => $value !== null);
            if (empty($validatedData)) {
                return response()->json(['message' => __('workspaces.no_fields_to_update')], 400);
            }

            $taskDTO = TaskDTO::fromArray([...$validatedData, 'project_id' => $this->workspaceService->getTaskById($id)->getProjectId()]);
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
    #[OA\Delete(
        path: '/tasks/{id}',
        operationId: 'deleteTask',
        summary: 'Delete task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Task not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->workspaceService->deleteTask($id);

            return response()->json(['message' => __('workspaces.task_deleted')]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
