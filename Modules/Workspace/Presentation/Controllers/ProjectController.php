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
 * Provides RESTful endpoints to create, read, update, and delete projects.
 * All operations require authentication via Sanctum token and proper workspace membership authorization.
 * Projects are organizational units within workspaces that contain tasks and facilitate team collaboration.
 *
 * Key Features:
 * - Create projects within workspaces (requires workspace membership)
 * - List all projects in a workspace (requires workspace membership)
 * - Retrieve single project by ID (requires workspace access)
 * - Update project properties (requires workspace membership)
 * - Delete projects with cascade task deletion (requires workspace membership)
 * - List all tasks within a project (requires project access)
 *
 * Authorization:
 * - All endpoints require valid Sanctum authentication token
 * - User must be a member of the workspace to perform any project operations
 * - Workspace owners and admins have full project management capabilities
 *
 * @see WorkspaceService For business logic implementation
 * @see ProjectResource For API response formatting
 * @see StoreProjectRequest For project creation validation rules
 * @see UpdateProjectRequest For project update validation rules
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
#[OA\Tag(name: 'Projects', description: 'Manage projects within workspaces')]
class ProjectController extends Controller
{
    /**
     * Create a new ProjectController instance.
     *
     * @param  WorkspaceService  $workspaceService  The workspace service dependency for project operations
     */
    public function __construct(private WorkspaceService $workspaceService) {}

    // ==================== LIST PROJECTS ====================

    /**
     * Retrieve all projects within a specific workspace.
     *
     * Returns a collection of projects that the authenticated user has access to.
     * User must be a member of the workspace to view its projects.
     * Projects are ordered by creation date (newest first).
     *
     * Authorization Requirements:
     * - User must be authenticated with valid Sanctum token
     * - User must be a member of the specified workspace
     * - Workspace must exist and be accessible
     *
     * Response includes:
     * - Project ID and name
     * - Project description and status
     * - Workspace association
     * - Creation and update timestamps
     * - Active status indicator
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @param  int  $workspaceId  The unique identifier of the workspace
     * @return JsonResponse JSON response containing project collection and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If workspace is not found or user lacks permission
     */
    #[OA\Get(
        path: '/api/v1/workspaces/{workspaceId}/projects',
        summary: 'List projects in a workspace',
        description: 'Retrieve all projects belonging to a specific workspace. Requires workspace membership for authorization.',
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
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ProjectResource')
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Projects retrieved successfully'
                        ),
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
        // Get the currently authenticated user from the request
        // Throws UnauthorizedHttpException if no valid token is provided
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        try {
            // Retrieve projects for the workspace and user
            // Service layer validates user is a member of the workspace
            $projects = $this->workspaceService->getProjectsByWorkspace($workspaceId, $user->id);

            // Return formatted JSON response with project collection
            return response()->json([
                'data' => ProjectResource::collection($projects),
                'message' => __('workspaces.projects_retrieved'),
            ]);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();

            // Handle user not being a member of the workspace
            // Return 403 Forbidden with appropriate error message
            if (str_contains($msg, 'not a member')) {
                return response()->json([
                    'message' => __('workspaces.not_member_of_workspace'),
                ], 403);
            }

            // Fallback for any other workspace-related error (e.g., workspace not found)
            // Return 404 Not Found with workspace-specific error message
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
     * The project is automatically set to ACTIVE status upon creation.
     *
     * Request Requirements:
     * - name: Required, 3-100 characters
     * - description: Optional, max 1000 characters
     * - status: Optional, defaults to 'active' (active, completed, archived)
     *
     * Authorization Requirements:
     * - User must be authenticated with valid Sanctum token
     * - User must be a member of the specified workspace
     * - Workspace must exist and be accessible
     *
     * @param  StoreProjectRequest  $request  The validated request containing project data
     * @param  int  $workspaceId  The unique identifier of the workspace
     * @return JsonResponse JSON response containing created project resource and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If workspace is not found or user lacks permission
     * @throws \Illuminate\Validation\ValidationException If request validation fails
     */
    #[OA\Post(
        path: '/api/v1/workspaces/{workspaceId}/projects',
        summary: 'Create a new project',
        description: 'Create a new project within a specific workspace. Requires workspace membership for authorization.',
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
            content: [
                'application/json' => new OA\MediaType(
                    schema: new OA\Schema(
                        required: ['name'],
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'Website Redesign'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Redesign company website'),
                            new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'archived'], example: 'active'),
                        ]
                    )
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/ProjectResource'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Project created successfully'
                        ),
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
        // Get the currently authenticated user from the request
        // Throws UnauthorizedHttpException if no valid token is provided
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        try {
            // Prepare project data from request input
            // Workspace ID is taken from route parameter to ensure consistency
            $projectData = [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'workspace_id' => $workspaceId,
            ];

            // Convert project data to DTO for service layer processing
            // DTO handles validation and data transformation
            $projectDTO = ProjectDTO::fromArray($projectData);

            // Create project via service layer
            // Service validates workspace exists and user is a member
            $project = $this->workspaceService->createProject($projectDTO, $user);

            // Return 201 Created with project resource and success message
            return response()->json([
                'data' => new ProjectResource($project),
                'message' => __('workspaces.project_created'),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            $errorMessage = $e->getMessage();

            // Handle workspace not found error
            // Check for both English and translated error messages
            if (str_contains($errorMessage, 'not found') || str_contains($errorMessage, __('workspaces.not_found_by_id'))) {
                return response()->json([
                    'message' => __('workspaces.workspace_not_found', ['id' => $workspaceId]),
                ], 404);
            }

            // Handle user not being a member of the workspace
            // Check for both English and translated error messages
            if (str_contains($errorMessage, __('workspaces.not_member')) || str_contains($errorMessage, 'not a member')) {
                return response()->json([
                    'message' => __('workspaces.not_member_of_workspace'),
                ], 403);
            }

            // Return validation error with error details
            return response()->json([
                'message' => $errorMessage,
                'errors' => [$errorMessage],
            ], 422);
        } catch (\Throwable $e) {
            // Log unexpected server errors for debugging and monitoring
            // Include workspace ID, user ID, exception details, and stack trace
            Log::error('ProjectController@store error', [
                'workspace_id' => $workspaceId,
                'user_id' => $user->id ?? 'unknown',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return generic server error
            // Include debug information only in debug mode
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
     *
     * Response includes:
     * - Project ID and name
     * - Project description and status
     * - Workspace association
     * - Active status indicator
     * - Creation and update timestamps
     *
     * Authorization Requirements:
     * - User must be authenticated with valid Sanctum token
     * - User must have access to the project's workspace
     * - Project must exist
     *
     * @param  int  $id  The unique identifier of the project
     * @return JsonResponse JSON response containing project details and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If project is not found
     */
    #[OA\Get(
        path: '/api/v1/projects/{id}',
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
                description: 'Project retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/ProjectResource'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Project retrieved successfully'
                        ),
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
            // Get project details from service layer
            // Service validates project exists and user has access
            $project = $this->workspaceService->getProjectById($id);

            // Return project resource with success message
            return response()->json([
                'data' => new ProjectResource($project),
                'message' => __('workspaces.project_retrieved'),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle project not found error
            // Check for specific error message pattern
            if (str_contains($e->getMessage(), 'Project not found')) {
                return response()->json([
                    'message' => __('workspaces.project_not_found', ['id' => $id]),
                ], 404);
            }

            // Return other project retrieval errors
            // Return 422 for validation or other errors
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
     * Supports partial updates - only provided fields will be updated.
     *
     * Request Requirements:
     * - name: Optional, 3-100 characters (if provided)
     * - description: Optional, max 1000 characters (if provided)
     * - status: Optional, must be one of: active, completed, archived (if provided)
     *
     * Authorization Requirements:
     * - User must be authenticated with valid Sanctum token
     * - User must be a member of the project's workspace
     * - At least one field must be provided for update
     *
     * @param  UpdateProjectRequest  $request  The validated request containing update data
     * @param  int  $id  The unique identifier of the project
     * @return JsonResponse JSON response containing updated project resource and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If project is not found or user lacks permission
     * @throws \Illuminate\Validation\ValidationException If request validation fails
     */
    #[OA\Put(
        path: '/api/v1/projects/{id}',
        summary: 'Update project',
        description: 'Update an existing project with new data. Supports partial updates. Requires workspace membership.',
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
            content: [
                'application/json' => new OA\MediaType(
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Updated Project Name'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Updated description'),
                            new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'archived'], nullable: true, example: 'active'),
                        ]
                    )
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/ProjectResource'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Project updated successfully'
                        ),
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
        // Get the authenticated user from the request
        // Throws UnauthorizedHttpException if no valid token is provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            // Filter out null values for partial update support
            // Only non-null fields will be updated in the database
            $validatedData = array_filter($request->validated(), fn ($value) => $value !== null);

            // Check if there are any fields to update
            // Return 400 Bad Request if no valid fields provided
            if (empty($validatedData)) {
                return response()->json([
                    'message' => __('workspaces.no_fields_to_update'),
                ], 400);
            }

            // Get project to retrieve workspace_id for DTO creation
            // Workspace ID is required to maintain referential integrity
            $project = $this->getProjectById($id);
            $projectDTO = ProjectDTO::fromArray([...$validatedData, 'workspace_id' => $project->getWorkspaceId()]);

            // Update the project via service layer
            // Service validates user is a member of the workspace
            $project = $this->workspaceService->updateProject($id, $projectDTO, $user);

            // Return updated project resource with success message
            return response()->json([
                'data' => new ProjectResource($project),
                'message' => __('workspaces.project_updated'),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle project not found or authorization errors
            // Return 404 Not Found with error message
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
     *
     * ⚠️ WARNING: This is a destructive operation that cannot be reversed.
     * All tasks, comments, and attachments associated with the project
     * will be cascade deleted.
     *
     * Authorization Requirements:
     * - User must be authenticated with valid Sanctum token
     * - User must be a member of the project's workspace
     * - Project must exist
     *
     * @param  int  $id  The unique identifier of the project
     * @return JsonResponse JSON response with success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If project is not found
     */
    #[OA\Delete(
        path: '/api/v1/projects/{id}',
        summary: 'Delete project',
        description: 'Permanently delete a project and all its associated tasks. This action cannot be undone. Requires workspace membership.',
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
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Project deleted successfully'
                        ),
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
            // Delete the project via service layer
            // Service validates project exists and user has permission
            $this->workspaceService->deleteProject($id);

            // Return success message
            return response()->json([
                'message' => __('workspaces.project_deleted'),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle project not found error
            // Return 404 Not Found with error message
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
     * Tasks are ordered by creation date (newest first).
     *
     * Response includes:
     * - Task ID and title
     * - Task description and status
     * - Priority level and due date
     * - Assigned user ID
     * - Overdue, completed, and assigned status indicators
     * - Creation and update timestamps
     *
     * Authorization Requirements:
     * - User must be authenticated with valid Sanctum token
     * - User must be a member of the project's workspace
     * - Project must exist
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @param  int  $projectId  The unique identifier of the project
     * @return JsonResponse JSON response containing task collection and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \InvalidArgumentException If project is not found or user lacks permission
     */
    #[OA\Get(
        path: '/api/v1/projects/{projectId}/tasks',
        summary: 'List tasks in a project',
        description: 'Retrieve all tasks belonging to a specific project. Requires workspace membership for authorization.',
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
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/TaskResource')
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Tasks retrieved successfully'
                        ),
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
        // Get the authenticated user from the request
        // Throws UnauthorizedHttpException if no valid token is provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        try {
            // Retrieve all tasks for the project from service layer
            // Service validates project exists and user has access
            $tasks = $this->workspaceService->getTasksByProject($projectId, $user->id);

            // Return formatted JSON response with task collection
            return response()->json([
                'data' => TaskResource::collection($tasks),
                'message' => __('workspaces.tasks_retrieved'),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Handle project not found or authorization errors
            // Return 404 Not Found with error message
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    // ==================== HELPER METHODS ====================

    /**
     * Helper method to retrieve a project by ID.
     *
     * Delegates to the workspace service for project retrieval.
     * Used internally to avoid code duplication in update method.
     *
     * @param  int  $id  The project ID to retrieve
     * @return ProjectEntity The retrieved project entity
     *
     * @throws \InvalidArgumentException If project is not found
     */
    private function getProjectById(int $id): ProjectEntity
    {
        return $this->workspaceService->getProjectById($id);
    }
}
