<?php

namespace Modules\Users\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Application\Services\UserService;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Presentation\Requests\LoginRequest;
use Modules\Users\Presentation\Requests\RegisterRequest;
use Modules\Users\Presentation\Resources\UserResource;

class AuthController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function register(RegisterRequest $request)
    {
        $userDTO = UserDTO::fromArray($request->validated());
        $user = $this->userService->createUser($userDTO);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user),
            'token' => $token,
            'message' => 'User registered successfully'
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user),
            'token' => $token,
            'message' => 'Login successful'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'data' => new UserResource($request->user())
        ]);
    }
}