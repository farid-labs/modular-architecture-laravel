<?php

namespace Modules\Users\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Application\Services\CachedUserService;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Users\Presentation\Requests\StoreUserRequest;
use Modules\Users\Presentation\Requests\UpdateUserRequest;
use Modules\Users\Presentation\Resources\UserResource;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(
    name: 'Users',
    description: 'API endpoints for managing users'
)]
class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CachedUserService $userService
    ) {
        // $this->authorizeResource(UserModel::class, 'user');
    }

    #[OA\Get(
        path: '/users',
        summary: 'Get all users',
        tags: ['Users'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/User')
                        ),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                ref: '#/components/schemas/ErrorResponse'
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        try {
            $users = $this->userService->getAllUsers();

            return response()->json([
                'data' => UserResource::collection($users),
                'message' => __('users.list_retrieved'),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => __('errors.unauthorized'),
                'hint' => __('errors.hint_check_permissions'),
            ], 401);
        } catch (Throwable $e) {
            Log::error('UserController@index error', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => __('errors.server_error'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    #[OA\Get(
        path: '/users/{id}',
        summary: 'Get user by ID',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                ref: '#/components/schemas/ErrorResponse'
            ),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $model = UserModel::findOrFail($id);
            $this->authorize('view', $model);

            $entity = $this->userService->getUserById($id);

            return response()->json([
                'data' => new UserResource($entity),
                'message' => __('users.retrieved'),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => __('users.not_found'),
                'resource' => 'user',
                'id' => $id,
            ], 404);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => __('errors.forbidden'),
                'hint' => __('errors.hint_insufficient_permissions'),
            ], 403);
        } catch (Throwable $e) {
            Log::error('UserController@show error', [
                'user_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => __('errors.server_error'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    #[OA\Post(
        path: '/users',
        summary: 'Create a new user',
        tags: ['Users'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserStoreRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Email already exists',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                ref: '#/components/schemas/ErrorResponse'
            ),
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $userDTO = UserDTO::fromArray($request->validated());
            $entity = $this->userService->createUser($userDTO);

            return response()->json([
                'data' => new UserResource($entity),
                'message' => __('users.created'),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('errors.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json([
                    'message' => __('users.email_exists'),
                    'field' => 'email',
                ], 409);
            }
            throw $e; // Let global handler manage other DB errors
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => __('errors.unauthorized'),
            ], 401);
        } catch (Throwable $e) {
            Log::error('UserController@store error', [
                'email' => $request->input('email'),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => __('errors.server_error'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    #[OA\Put(
        path: '/users/{id}',
        summary: 'Update user',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Cannot update other users',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                ref: '#/components/schemas/ErrorResponse'
            ),
        ]
    )]
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        Log::info('=== REQUEST DEBUG START ===');
        Log::info('Raw request content:', ['body' => $request->getContent()]);
        Log::info('All request data:', $request->all());
        Log::info('Validated data:', $request->validated());
        Log::info('Route parameter id:', ['id' => $id]);
        Log::info('=== REQUEST DEBUG END ===');
        try {
            $model = UserModel::findOrFail($id);
            $this->authorize('update', $model);

            $userDTO = UserDTO::fromArray($request->validated());
            $updatedEntity = $this->userService->updateUser($id, $userDTO);

            Log::info('User updated successfully in DB', [
                'user_id' => $id,
                'new_name' => $updatedEntity->getName()->getValue(),
                'new_email' => $updatedEntity->getEmail()->getValue(),
            ]);

            return response()->json([
                'data' => new UserResource($updatedEntity),
                'message' => __('users.updated'),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => __('users.not_found'),
                'resource' => 'user',
                'id' => $id,
            ], 404);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => __('errors.forbidden'),
                'hint' => __('errors.hint_cannot_update_others'),
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('errors.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('UserController@update error', [
                'user_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => __('errors.server_error'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    #[OA\Delete(
        path: '/users/{id}',
        summary: 'Delete user',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Cannot delete other users',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                ref: '#/components/schemas/ErrorResponse'
            ),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $model = UserModel::findOrFail($id);
            $this->authorize('delete', $model);

            $this->userService->deleteUser($id);

            return response()->json([
                'message' => __('users.deleted'),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => __('users.not_found'),
                'resource' => 'user',
                'id' => $id,
            ], 404);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => __('errors.forbidden'),
                'hint' => __('errors.hint_cannot_delete_others'),
            ], 403);
        } catch (Throwable $e) {
            Log::error('UserController@destroy error', [
                'user_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => __('errors.server_error'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
