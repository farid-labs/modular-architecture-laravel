<?php

namespace Modules\Users\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Modules\Users\Application\Services\CachedUserService;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Presentation\Requests\StoreUserRequest;
use Modules\Users\Presentation\Requests\UpdateUserRequest;
use Modules\Users\Presentation\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(
        private CachedUserService $userService
    ) {
        $this->authorizeResource(\Modules\Users\Domain\Entities\User::class, 'user');
    }

    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();
        return response()->json([
            'data' => UserResource::collection($users),
            'message' => 'Users retrieved successfully'
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        
        // Authorization check
        $this->authorize('view', $user);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User retrieved successfully'
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $userDTO = UserDTO::fromArray($request->validated());
        $user = $this->userService->createUser($userDTO);
        
        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User created successfully'
        ], 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        
        // Authorization check
        $this->authorize('update', $user);

        $userDTO = UserDTO::fromArray($request->validated());
        $updatedUser = $this->userService->updateUser($id, $userDTO);
        
        return response()->json([
            'data' => new UserResource($updatedUser),
            'message' => 'User updated successfully'
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        
        // Authorization check
        $this->authorize('delete', $user);

        $this->userService->deleteUser($id);
        
        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}