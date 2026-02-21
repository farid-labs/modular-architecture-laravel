<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Workspace\Application\DTOs\ProjectDTO;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Presentation\Requests\StoreProjectRequest;
use Modules\Workspace\Presentation\Requests\UpdateProjectRequest;
use Modules\Workspace\Presentation\Resources\ProjectResource;
use Modules\Workspace\Presentation\Resources\TaskResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Controller responsible for managing projects within workspaces.
 *
 * Provides endpoints to create, read, update, and delete projects.
 * All operations require authentication and proper workspace membership authorization.
 */
#[OA\Tag(name: 'Projects', description: 'Manage projects within workspaces')]
class ProjectController extends Controller
{
    /**
     * Create a new ProjectController instance.
     *
     * @param  WorkspaceService  $workspaceService  The workspace service dependency
     */
    public function __construct(private WorkspaceService $workspaceService) {}

    // ==================== LIST PROJECTS ====================
    /**
     * Retrieve all projects within a specific workspace.
     *
     * Returns a collection of projects that the authenticated user has access to.
     * User must be a member of the workspace to view its projects.
     */
    #[OA\Get(
        path: '/workspaces/{workspaceId}/projects',
        operationId: 'listProjects',
        summary: 'List projects in a workspace',
        description: 'Retrieve all projects belonging to a specific workspace. ' .
            'Requires workspace membership for authorization.',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                description: 'The unique identifier of the workspace',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Projects retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ProjectResource')
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'Projects retrieved successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized - Invalid or missing authentication token'),
            new OA\Response(response: 403, description: 'Forbidden - User is not a member of this workspace'),
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

    // ==================== CREATE PROJECT ====================
    /**
     * Create a new project within a workspace.
     *
     * Creates a project with the provided name and optional description.
     * User must be a member of the workspace to create projects.
     */
    #[OA\Post(
        path: '/workspaces/{workspaceId}/projects',
        operationId: 'createProject',
        summary: 'Create a new project',
        description: 'Create a new project within a specific workspace. ' .
            'Requires workspace membership for authorization.',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                description: 'The unique identifier of the workspace',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Website Redesign'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Redesign company website'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'archived'], example: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProjectResource'),
                        new OA\Property(property: 'message', type: 'string', example: 'Project created successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized - Invalid or missing authentication token'),
            new OA\Response(response: 403, description: 'Forbidden - User is not a member of this workspace'),
            new OA\Response(response: 404, description: 'Workspace not found'),
            new OA\Response(response: 422, description: 'Validation error - Invalid request data'),
        ]
    )]
    public function store(StoreProjectRequest $request, int $workspaceId): JsonResponse
    {
        // Get the currently authenticated user
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
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
            if (str_contains($errorMessage, 'not found') || str_contains($errorMessage, __('workspaces.not_found_by_id'))) {
                return response()->json([
                    'message' => __('workspaces.workspace_not_found', ['id' => $workspaceId]),
                ], 404);
            }

            // Handle user not being a member of the workspace
            if (str_contains($errorMessage, __('workspaces.not_member')) || str_contains($errorMessage, 'not a member')) {
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

    // ==================== GET PROJECT BY ID ====================
    /**
     * Retrieve a single project by its unique identifier.
     *
     * Returns detailed information about a specific project.
     * User must have access to the project's workspace.
     */
    #[OA\Get(
        path: '/projects/{id}',
        operationId: 'getProject',
        summary: 'Get project by ID',
        description: 'Retrieve detailed information about a specific project by its unique identifier.',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The unique identifier of the project',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project retrieved successfully',  // ✅ Fixed: Static string instead of __()
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProjectResource'),
                        new OA\Property(property: 'message', type: 'string', example: 'Project retrieved successfully'),  // ✅ Fixed
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized - Invalid or missing authentication token'),
            new OA\Response(response: 404, description: 'Project not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        try {
            // Get project details
            $project = $this->workspaceService->getProjectById($id);

            return response()->json([
                'data' => new ProjectResource($project),
                'message' => __('workspaces.project_retrieved'),  // ✅ This is OK - inside method body
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle project not found
            if (str_contains($e->getMessage(), 'Project not found')) {
                return response()->json([
                    'message' => __('workspaces.project_not_found', ['id' => $id]),
                ], 404);
            }

            // Return other project retrieval errors
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ==================== UPDATE PROJECT ====================
    /**
     * Update an existing project.
     *
     * Allows partial updates to project properties (name, description, status).
     * Only workspace members can update projects.
     */
    #[OA\Put(
        path: '/projects/{id}',
        operationId: 'updateProject',
        summary: 'Update project',
        description: 'Update an existing project with new data. ' .
            'Supports partial updates. Requires workspace membership.',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'id',
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
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Updated Project Name'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Updated description'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'archived'], nullable: true, example: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project updated successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProjectResource'),
                        new OA\Property(property: 'message', type: 'string', example: 'Project updated successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized - Invalid or missing authentication token'),
            new OA\Response(response: 403, description: 'Forbidden - User is not a member of this workspace'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 422, description: 'Validation error - Invalid request data'),
        ]
    )]
    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        // Get the authenticated user
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            // Filter out null values for partial update
            $validatedData = array_filter($request->validated(), fn($value) => $value !== null);

            // Check if there are any fields to update
            if (empty($validatedData)) {
                return response()->json([
                    'message' => __('workspaces.no_fields_to_update'),
                ], 400);
            }

            // Get project to retrieve workspace_id
            $project = $this->getProjectById($id);
            $projectDTO = ProjectDTO::fromArray([...$validatedData, 'workspace_id' => $project->getWorkspaceId()]);

            // Update the project
            $project = $this->workspaceService->updateProject($id, $projectDTO, $user);

            return response()->json([
                'data' => new ProjectResource($project),
                'message' => __('workspaces.project_updated'),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle project not found or authorization errors
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    // ==================== DELETE PROJECT ====================
    /**
     * Permanently delete a project.
     *
     * Removes the project and all associated tasks from the system.
     * This action cannot be undone. Only workspace members can delete projects.
     */
    #[OA\Delete(
        path: '/projects/{id}',
        operationId: 'deleteProject',
        summary: 'Delete project',
        description: 'Permanently delete a project and all its associated tasks. ' .
            'This action cannot be undone. Requires workspace membership.',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The unique identifier of the project',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project deleted successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Project deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized - Invalid or missing authentication token'),
            new OA\Response(response: 403, description: 'Forbidden - User is not a member of this workspace'),
            new OA\Response(response: 404, description: 'Project not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            // Delete the project
            $this->workspaceService->deleteProject($id);

            return response()->json([
                'message' => __('workspaces.project_deleted'),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle project not found
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    // ==================== LIST TASKS BY PROJECT ====================
    /**
     * Retrieve all tasks within a specific project.
     *
     * Returns a collection of tasks that belong to the specified project.
     * User must be a member of the project's workspace.
     */
    #[OA\Get(
        path: '/projects/{projectId}/tasks',
        operationId: 'listTasksByProject',
        summary: 'List tasks in a project',
        description: 'Retrieve all tasks belonging to a specific project. ' .
            'Requires workspace membership for authorization.',
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
            new OA\Response(response: 401, description: 'Unauthorized - Invalid or missing authentication token'),
            new OA\Response(response: 403, description: 'Forbidden - User is not a member of this project'),
            new OA\Response(response: 404, description: 'Project not found'),
        ]
    )]
    public function indexTasks(Request $request, int $projectId): JsonResponse
    {
        // Get the authenticated user
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            // Retrieve all tasks for the project
            $tasks = $this->workspaceService->getTasksByProject($projectId, $user->id);

            return response()->json([
                'data' => TaskResource::collection($tasks),
                'message' => __('workspaces.tasks_retrieved'),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle project not found or authorization errors
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Helper method to retrieve a project by ID.
     *
     * @param  int  $id  The project ID
     *
     * @throws \InvalidArgumentException if project not found
     */
    private function getProjectById(int $id): ProjectEntity
    {
        return $this->workspaceService->getProjectById($id);
    }
}
