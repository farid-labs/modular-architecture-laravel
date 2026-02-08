<?php

namespace Modules\Users\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Modules\Users\Application\Services\UserService;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Presentation\Requests\StoreUserRequest;
use Modules\Users\Presentation\Requests\UpdateUserRequest;
use Modules\Users\Presentation\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();
        return response()->json([
            'data' => UserResource::collection($users),
            'message' => 'Users retrieved successfully'
        ]);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);
            return response()->json([
                'data' => new UserResource($user),
                'message' => 'User retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $userDTO = UserDTO::fromArray($request->validated());
            $user = $this->userService->createUser($userDTO);
            
            return response()->json([
                'data' => new UserResource($user),
                'message' => 'User created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $userDTO = UserDTO::fromArray($request->validated());
            $user = $this->userService->updateUser($id, $userDTO);
            
            return response()->json([
                'data' => new UserResource($user),
                'message' => 'User updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);
            return response()->json([
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }
}