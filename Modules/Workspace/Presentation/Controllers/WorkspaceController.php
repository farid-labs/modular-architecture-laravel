<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Presentation\Requests\StoreWorkspaceRequest;
use Modules\Workspace\Presentation\Requests\UpdateWorkspaceRequest;
use Modules\Workspace\Presentation\Resources\WorkspaceResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[OA\Tag(name: 'Workspaces', description: 'Create and manage collaborative workspaces with member access control')]
class WorkspaceController extends Controller
{
    public function __construct(private WorkspaceService $workspaceService) {}

    #[OA\Get(
        path: '/v1/workspaces',
        operationId: 'listWorkspaces',
        summary: 'List user workspaces',
        description: 'Get all workspaces the authenticated user is a member of',
        security: [['bearerAuth' => []]],
        tags: ['Workspaces'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/WorkspaceResource')
                        ),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', ref: '#/components/schemas/ErrorResponse'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }
        $workspaces = $this->workspaceService->getWorkspacesByUser($user->id);

        return response()->json([
            'data' => WorkspaceResource::collection($workspaces),
            'message' => 'Workspaces retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/v1/workspaces/{slug}',
        operationId: 'getWorkspaceBySlug',
        summary: 'Get workspace by slug',
        description: 'Retrieve a single workspace using its slug',
        security: [['bearerAuth' => []]],
        tags: ['Workspaces'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/WorkspaceResource'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 404, description: 'Workspace not found', ref: '#/components/schemas/ErrorResponse'),
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $workspace = $this->workspaceService->getWorkspaceBySlug($slug);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
            'message' => 'Workspace retrieved successfully',
        ]);
    }

    #[OA\Post(
        path: '/v1/workspaces',
        operationId: 'createWorkspace',
        summary: 'Create new workspace',
        description: 'Create a new workspace owned by the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Workspaces'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Marketing Team'),
                    new OA\Property(property: 'description', type: 'string', example: 'Marketing campaigns workspace', nullable: true),
                    new OA\Property(property: 'slug', type: 'string', example: 'marketing-team', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Workspace created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/WorkspaceResource'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 422, description: 'Validation error', ref: '#/components/schemas/ErrorResponse'),
        ]
    )]
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspaceDTO = WorkspaceDTO::fromArray($request->validated());
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        $workspace = $this->workspaceService->createWorkspace($workspaceDTO, $user);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
            'message' => 'Workspace created successfully',
        ], 201);
    }

    #[OA\Put(
        path: '/v1/workspaces/{id}',
        operationId: 'updateWorkspace',
        summary: 'Update workspace',
        description: 'Update an existing workspace (owner only)',
        security: [['bearerAuth' => []]],
        tags: ['Workspaces'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Workspace updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/WorkspaceResource'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 403, description: 'Forbidden - Not workspace owner', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 404, description: 'Workspace not found', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 422, description: 'Validation error', ref: '#/components/schemas/ErrorResponse'),
        ]
    )]
    public function update(UpdateWorkspaceRequest $request, int $id): JsonResponse
    {
        $workspaceDTO = WorkspaceDTO::fromArray($request->validated());
        $workspace = $this->workspaceService->updateWorkspace($id, $workspaceDTO);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
            'message' => 'Workspace updated successfully',
        ]);
    }

    #[OA\Delete(
        path: '/v1/workspaces/{id}',
        operationId: 'deleteWorkspace',
        summary: 'Delete workspace',
        description: 'Permanently delete a workspace (owner only)',
        security: [['bearerAuth' => []]],
        tags: ['Workspaces'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Workspace deleted successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 401, description: 'Unauthorized', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 403, description: 'Forbidden - Not workspace owner', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 404, description: 'Workspace not found', ref: '#/components/schemas/ErrorResponse'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $this->workspaceService->deleteWorkspace($id);

        return response()->json([
            'message' => 'Workspace deleted successfully',
        ]);
    }

    #[OA\Post(
        path: '/v1/workspaces/{workspaceId}/members',
        operationId: 'addWorkspaceMember',
        summary: 'Add member to workspace',
        description: 'Add a user as a member to the workspace with specified role',
        security: [['bearerAuth' => []]],
        tags: ['Workspaces'],
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'role'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 5),
                    new OA\Property(property: 'role', type: 'string', enum: ['owner', 'admin', 'member'], example: 'member'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Member added successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 401, description: 'Unauthorized', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 403, description: 'Forbidden - Insufficient permissions', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 404, description: 'Workspace or user not found', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 422, description: 'Validation error', ref: '#/components/schemas/ErrorResponse'),
        ]
    )]
    public function addMember(Request $request, int $workspaceId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:owner,admin,member',
        ]);

        $this->workspaceService->addUserToWorkspace(
            $workspaceId,
            $request->user_id,
            $request->role
        );

        return response()->json([
            'message' => 'Member added successfully',
        ]);
    }

    #[OA\Delete(
        path: '/v1/workspaces/{workspaceId}/members',
        operationId: 'removeWorkspaceMember',
        summary: 'Remove member from workspace',
        description: 'Remove a user from workspace membership',
        security: [['bearerAuth' => []]],
        tags: ['Workspaces'],
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [new OA\Property(property: 'user_id', type: 'integer', example: 5)]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Member removed successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 401, description: 'Unauthorized', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 403, description: 'Forbidden - Insufficient permissions', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 404, description: 'Workspace or user not found', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 422, description: 'Validation error', ref: '#/components/schemas/ErrorResponse'),
        ]
    )]
    public function removeMember(Request $request, int $workspaceId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $this->workspaceService->removeUserFromWorkspace(
            $workspaceId,
            $request->user_id
        );

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }
}
