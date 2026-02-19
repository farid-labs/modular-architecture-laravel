<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Workspace\Application\DTOs\ProjectDTO;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Presentation\Requests\StoreProjectRequest;
use Modules\Workspace\Presentation\Resources\ProjectResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[OA\Tag(name: 'Projects', description: 'Manage projects within workspaces')]
class ProjectController extends Controller
{
    // Inject WorkspaceService into the controller
    public function __construct(private WorkspaceService $workspaceService) {}

    // List all projects in a specific workspace
    #[OA\Get(
        path: '/workspaces/{workspaceId}/projects',
        operationId: 'listProjects',
        summary: 'List projects in a workspace',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProjectResource')),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Not a member of this workspace'),
            new OA\Response(response: 404, description: 'Workspace not found'),
        ]
    )]
    public function index(Request $request, int $workspaceId): JsonResponse
    {
        // Get the currently authenticated user
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        try {
            // Retrieve projects for the workspace and user
            $projects = $this->workspaceService->getProjectsByWorkspace($workspaceId, $user->id);

            return response()->json([
                'data' => ProjectResource::collection($projects),
                'message' => __('workspaces.projects_retrieved'),
            ]);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();

            // Handle user not being a member of the workspace
            if (str_contains($msg, 'not a member')) {
                return response()->json([
                    'message' => __('workspaces.not_member_of_workspace'),
                ], 403);
            }

            // Fallback for any other workspace-related error (e.g., workspace not found)
            return response()->json([
                'message' => __('workspaces.workspace_not_found', ['id' => $workspaceId]),
            ], 404);
        }
    }

    // Create a new project in a workspace
    #[OA\Post(
        path: '/workspaces/{workspaceId}/projects',
        operationId: 'createProject',
        summary: 'Create a new project',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Website Redesign'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'archived'], example: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProjectResource'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreProjectRequest $request, int $workspaceId): JsonResponse
    {
        // Get the currently authenticated user
        $user = $request->user();
        if ($user === null) {
            throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('Unauthorized');
        }

        try {
            // Prepare project data
            $projectData = [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'workspace_id' => $workspaceId,
            ];

            // Convert project data to DTO and create project
            $projectDTO = ProjectDTO::fromArray($projectData);
            $project = $this->workspaceService->createProject($projectDTO, $user);

            return response()->json([
                'data' => new ProjectResource($project),
                'message' => __('workspaces.project_created'),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            $errorMessage = $e->getMessage();

            // Handle workspace not found
            if (
                str_contains($errorMessage, 'not found') ||
                str_contains($errorMessage, __('workspaces.not_found_by_id'))
            ) {
                return response()->json([
                    'message' => __('workspaces.workspace_not_found', ['id' => $workspaceId]),
                ], 404);
            }

            // Handle user not being a member of the workspace
            if (
                str_contains($errorMessage, __('workspaces.not_member')) ||
                str_contains($errorMessage, 'not a member')
            ) {
                return response()->json([
                    'message' => __('workspaces.not_member_of_workspace'),
                ], 403);
            }

            // Return validation error
            return response()->json([
                'message' => $errorMessage,
                'errors' => [$errorMessage],
            ], 422);
        } catch (\Throwable $e) {
            // Log unexpected server errors
            Log::error('ProjectController@store error', [
                'workspace_id' => $workspaceId,
                'user_id' => $user->id ?? 'unknown',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return generic server error
            return response()->json([
                'message' => __('errors.server_error'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // Retrieve a single project by its ID
    #[OA\Get(
        path: '/projects/{id}',
        operationId: 'getProject',
        summary: 'Get project by ID',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProjectResource'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Project not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        // Get project details
        $project = $this->workspaceService->getProjectById($id);

        return response()->json([
            'data' => new ProjectResource($project),
            'message' => 'Project retrieved successfully',
        ]);
    }
}
