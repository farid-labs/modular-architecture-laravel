<?php

namespace Modules\Users\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Application\Services\UserService;
use Modules\Users\Domain\Entities\UserEntity;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Users\Presentation\Requests\LoginRequest;
use Modules\Users\Presentation\Requests\RegisterRequest;
use Modules\Users\Presentation\Resources\UserResource;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints for authentication"
 * )
 */
class AuthController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $userDTO = UserDTO::fromArray($request->validated());
        $entity = $this->userService->createUser($userDTO);

        $model = $this->mapEntityToModelForAuth($entity);
        $token = $model->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => new UserResource($entity),
            'token' => $token,
            'message' => 'User registered successfully',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        /** @var UserModel $user */
        $user = Auth::user();

        $entity = $this->userService->getUserById($user->id);
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => new UserResource($entity),
            'token' => $token,
            'message' => 'Login successful',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var UserModel $user */
        $user = $request->user();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var UserModel $user */
        $user = $request->user();

        $entity = $this->userService->getUserById($user->id);

        return response()->json([
            'data' => new UserResource($entity),
        ]);
    }

    private function mapEntityToModelForAuth(UserEntity $entity): UserModel
    {
        $model = new UserModel;
        $model->id = $entity->getId();
        $model->name = $entity->getFullName();
        $model->email = $entity->getEmail()->getValue();
        $model->password = $entity->getPassword() ?? '';  // Default to empty string if null
        $model->is_admin = $entity->isAdmin();
        $model->email_verified_at = $entity->getEmailVerifiedAt();
        $model->created_at = $entity->getCreatedAt() ?? now()->toImmutable();  // Default to current time if null
        $model->updated_at = $entity->getUpdatedAt() ?? now()->toImmutable();  // Default to current time if null

        return $model;
    }
}
