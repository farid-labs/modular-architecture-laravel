<?php

namespace Modules\Users\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Application\Services\CachedUserService;
use Modules\Users\Infrastructure\Persistence\Models\User;
use Modules\Users\Presentation\Requests\StoreUserRequest;
use Modules\Users\Presentation\Requests\UpdateUserRequest;
use Modules\Users\Presentation\Resources\UserResource;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Users',
    description: 'API endpoints for managing users'
)]
class UserController extends Controller
{
    public function __construct(
        private CachedUserService $userService
    ) {

        // $this->authorizeResource(User::class, 'user');
    }

    #[OA\Get(
        path: '/v1/users',
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
                description: 'Unauthorized'
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();

        return response()->json([
            'data' => UserResource::collection($users),
            'message' => 'Users retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/v1/users/{id}',
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
                response: 404,
                description: 'User not found'
            ),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        // Authorization check
        $this->authorize('view', $user);

        Log::debug('Controller show invoked', [
            'user_param' => $user->id,
        ]);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User retrieved successfully',
        ]);
    }

    #[OA\Post(
        path: '/v1/users',
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
                response: 422,
                description: 'Validation error'
            ),
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $userDTO = UserDTO::fromArray($request->validated());
        $user = $this->userService->createUser($userDTO);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User created successfully',
        ], 201);
    }

    #[OA\Put(
        path: '/v1/users/{id}',
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
                response: 404,
                description: 'User not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
        ]
    )]
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        // Authorization check
        $this->authorize('update', $user);

        $userDTO = UserDTO::fromArray($request->validated());
        $updatedUser = $this->userService->updateUser($id, $userDTO);

        return response()->json([
            'data' => new UserResource($updatedUser),
            'message' => 'User updated successfully',
        ]);
    }

    #[OA\Delete(
        path: '/v1/users/{id}',
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
                response: 404,
                description: 'User not found'
            ),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        // Authorization check
        $this->authorize('delete', $user);

        $this->userService->deleteUser($id);

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
