<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Presentation\Requests\StoreTaskRequest;
use Modules\Workspace\Presentation\Resources\TaskResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[OA\Tag(name: 'Tasks', description: 'Manage tasks within projects')]
class TaskController extends Controller
{
    public function __construct(private WorkspaceService $workspaceService) {}

    #[OA\Post(
        path: '/v1/projects/{projectId}/tasks',
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
            new OA\Response(
                response: 201,
                description: 'Task created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function store(StoreTaskRequest $request, int $projectId): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        $taskDTO = TaskDTO::fromArray([
            ...$request->validated(),
            'project_id' => $projectId,
        ]);

        $task = $this->workspaceService->createTask($taskDTO, $user);

        return response()->json([
            'data' => new TaskResource($task),
            'message' => 'Task created successfully',
        ], 201);
    }

    #[OA\Get(
        path: '/v1/tasks/{id}',
        operationId: 'getTask',
        summary: 'Get task by ID',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $task = $this->workspaceService->getTaskById($id);

        return response()->json([
            'data' => new TaskResource($task),
            'message' => 'Task retrieved successfully',
        ]);
    }

    #[OA\Put(
        path: '/v1/tasks/{id}/complete',
        operationId: 'completeTask',
        summary: 'Mark task as completed',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        $task = $this->workspaceService->completeTask($id, $user);

        return response()->json([
            'data' => new TaskResource($task),
            'message' => 'Task completed successfully',
        ]);
    }
}
