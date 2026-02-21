<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
    // Inject WorkspaceService into the controller
    public function __construct(private WorkspaceService $workspaceService) {}

    // ==================== LIST WORKSPACES ====================
    #[OA\Get(
        path: '/workspaces',
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
        // Get authenticated user
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        // Retrieve all workspaces for the user
        $workspaces = $this->workspaceService->getWorkspacesByUser($user->id);

        return response()->json([
            'data' => WorkspaceResource::collection($workspaces),
            'message' => __('workspaces.workspaces_retrieved'),
        ]);
    }

    // ==================== GET WORKSPACE BY SLUG ====================
    #[OA\Get(
        path: '/workspaces/{slug}',
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
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Workspace not found'),
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        // Prevent numeric slugs
        if (is_numeric($slug)) {
            return response()->json([
                'message' => __('workspaces.invalid_parameter'),
                'hint' => __('workspaces.hint_use_list'),
            ], 400);
        }

        try {
            // Retrieve workspace by slug
            $workspace = $this->workspaceService->getWorkspaceBySlug($slug);

            return response()->json([
                'data' => new WorkspaceResource($workspace),
                'message' => __('workspaces.retrieved'),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle workspace not found
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    // ==================== CREATE WORKSPACE ====================
    #[OA\Post(
        path: '/workspaces',
        operationId: 'createWorkspace',
        summary: 'Create new workspace',
        description: 'Create a new workspace owned by the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Workspaces'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'slug'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Marketing Team'),
                    new OA\Property(
                        property: 'slug',
                        type: 'string',
                        example: 'marketing-team',
                        pattern: '^[a-z0-9]+(?:-[a-z0-9]+)*$'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        example: 'Marketing campaigns workspace'
                    ),
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
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        // Get authenticated user
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        // Convert validated request data into DTO
        $workspaceDTO = WorkspaceDTO::fromArray($request->validated());

        // Create workspace via service
        $workspace = $this->workspaceService->createWorkspace($workspaceDTO, $user);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
            'message' => __('workspaces.workspace_created'),
        ], 201);
    }

    // ==================== UPDATE WORKSPACE ====================
    #[OA\Put(
        path: '/workspaces/{id}',
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
        // Validate workspace ID
        if ($id <= 0) {
            return response()->json([
                'message' => __('workspaces.invalid_id_format'),
                'hint' => __('workspaces.hint_valid_id'),
            ], 400);
        }

        try {
            // Remove null fields from validated data
            $validatedData = array_filter($request->validated(), fn ($value) => $value !== null);

            if (empty($validatedData)) {
                return response()->json([
                    'message' => __('workspaces.no_fields_to_update'),
                ], 400);
            }

            // Convert to DTO and update workspace
            $workspaceDTO = WorkspaceDTO::fromArray($validatedData);
            $workspace = $this->workspaceService->updateWorkspace($id, $workspaceDTO);

            return response()->json([
                'data' => new WorkspaceResource($workspace),
                'message' => __('workspaces.updated'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors
            return response()->json([
                'message' => __('workspaces.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            // Workspace not found
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // Log unexpected errors
            Log::error('Workspace update failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => __('workspaces.update_failed'),
            ], 500);
        }
    }

    // ==================== DELETE WORKSPACE ====================
    #[OA\Delete(
        path: '/workspaces/{id}',
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
        try {
            // Attempt to delete workspace
            $deleted = $this->workspaceService->deleteWorkspace($id);

            if (! $deleted) {
                return response()->json([
                    'message' => __('workspaces.not_found_by_id', ['id' => $id]),
                ], 404);
            }

            return response()->json([
                'message' => __('workspaces.deleted'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    // ==================== ADD WORKSPACE MEMBER ====================
    #[OA\Post(
        path: '/workspaces/{workspaceId}/members',
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
        // Validate request input
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:owner,admin,member',
        ]);

        // Add user to workspace via service
        $result = $this->workspaceService->addUserToWorkspace(
            $workspaceId,
            $request->user_id,
            $request->role
        );

        if (! $result) {
            return response()->json([
                'message' => __('workspaces.not_found_by_id', ['id' => $workspaceId]),
            ], 404);
        }

        return response()->json([
            'message' => __('workspaces.member_added'),
        ]);
    }

    // ==================== REMOVE WORKSPACE MEMBER ====================
    #[OA\Delete(
        path: '/workspaces/{workspaceId}/members',
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
            new OA\Response(response: 204, description: 'Member removed successfully'),
            new OA\Response(response: 401, description: 'Unauthorized', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 403, description: 'Forbidden - Insufficient permissions', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 404, description: 'Workspace, user or membership not found', ref: '#/components/schemas/ErrorResponse'),
            new OA\Response(response: 422, description: 'Validation error', ref: '#/components/schemas/ErrorResponse'),
        ]
    )]
    public function removeMember(Request $request, int $workspaceId): JsonResponse
    {
        // Validate user_id
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $targetUserId = $request->input('user_id');

        // Remove user from workspace via service
        $affected = $this->workspaceService->removeUserFromWorkspace($workspaceId, $targetUserId);

        if ($affected === 0) {
            return response()->json([
                'message' => __('workspaces.membership_not_found', [
                    'user_id' => $targetUserId,
                    'workspace_id' => $workspaceId,
                ]),
            ], 404);
        }

        return response()->json([
            'message' => __('workspaces.member_removed'),
        ]);
    }

    // ==================== LIST WORKSPACE MEMBERS ====================
    #[OA\Get(
        path: '/workspaces/{workspaceId}/members',
        operationId: 'listWorkspaceMembers',
        summary: 'List workspace members',
        security: [['bearerAuth' => []]],
        tags: ['Workspaces'],
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Members retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Workspace not found'),
        ]
    )]
    public function indexMembers(Request $request, int $workspaceId): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            $members = $this->workspaceService->getWorkspaceMembers($workspaceId, $user->id);

            return response()->json([
                'data' => $members,
                'message' => __('workspaces.members_retrieved'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
