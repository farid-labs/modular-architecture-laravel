<?php

namespace Modules\Users\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Application\Services\UserService;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Users\Presentation\Requests\LoginRequest;
use Modules\Users\Presentation\Requests\RegisterRequest;
use Modules\Users\Presentation\Resources\UserResource;
use OpenApi\Attributes as OA;
use PDOException;
use Throwable;

#[OA\Tag(
    name: 'Authentication',
    description: 'User authentication and session management endpoints'
)]
class AuthController extends Controller
{
    public function __construct(private UserService $userService) {}

    #[OA\Post(
        path: '/v1/auth/register',
        operationId: 'registerUser',
        summary: 'Register a new user',
        description: 'Create a new user account and return authentication token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'securepassword123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'securepassword123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Email already exists',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'field', type: 'string', example: 'email'),
                    ]
                )
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
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $userDTO = UserDTO::fromArray($request->validated());

            $entity = $this->userService->createUser($userDTO);

            $model = UserModel::findOrFail($entity->getId());
            $token = $model->createToken('auth-token')->plainTextToken;

            return response()->json([
                'data' => new UserResource($entity),
                'token' => $token,
                'message' => __('auth.register_success'),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('errors.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'email')) {
                Log::warning('Registration attempt with existing email', ['email' => $request->input('email')]);

                return response()->json([
                    'message' => __('auth.email_exists'),
                    'field' => 'email',
                ], 409);
            }
            throw $e;
        } catch (Throwable $e) {
            Log::error('AuthController@register error', [
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

    #[OA\Post(
        path: '/v1/auth/login',
        operationId: 'loginUser',
        summary: 'Authenticate user',
        description: 'Login with credentials and receive authentication token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'securepassword123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'hint', type: 'string', example: 'Check your email and password'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 429,
                description: 'Too many login attempts',
                ref: '#/components/schemas/ErrorResponse'
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                ref: '#/components/schemas/ErrorResponse'
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            if (! Auth::attempt($credentials)) {
                Log::warning('Failed login attempt', ['email' => $credentials['email']]);

                return response()->json([
                    'message' => __('auth.invalid_credentials'),
                    'hint' => __('auth.hint_check_credentials'),
                ], 401);
            }

            /** @var ?UserModel $user */
            $user = Auth::user();
            if (! $user) {
                Log::error('Auth::user() returned null after successful attempt');

                return response()->json([
                    'message' => __('auth.authentication_failed'),
                ], 500);
            }

            $entity = $this->userService->getUserById($user->id);
            $token = $user->createToken('auth-token')->plainTextToken;

            Log::channel('auth')->info('User login successful', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'data' => new UserResource($entity),
                'token' => $token,
                'message' => __('auth.login_success'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('errors.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthenticationException $e) {
            Log::warning('Authentication exception during login', ['email' => $request->input('email')]);

            return response()->json([
                'message' => __('auth.authentication_failed'),
                'hint' => __('auth.hint_session_expired'),
            ], 401);
        } catch (Throwable $e) {
            Log::error('AuthController@login error', [
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

    #[OA\Post(
        path: '/v1/auth/logout',
        operationId: 'logoutUser',
        summary: 'Logout user',
        description: 'Invalidate current authentication token',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or missing token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'hint', type: 'string', example: 'Provide a valid authentication token'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                ref: '#/components/schemas/ErrorResponse'
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        try {
            /** @var ?UserModel $user */
            $user = $request->user();
            if (! $user) {
                Log::warning('Logout attempt without authenticated user');

                return response()->json([
                    'message' => __('auth.unauthorized'),
                    'hint' => __('auth.hint_provide_valid_token'),
                ], 401);
            }

            $user->tokens()->delete();
            Log::channel('auth')->info('User logout successful', ['user_id' => $user->id]);

            return response()->json([
                'message' => __('auth.logout_success'),
            ]);
        } catch (Throwable $e) {
            Log::error('AuthController@logout error', [
                'user_id' => optional($request->user())->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => __('errors.server_error'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    #[OA\Get(
        path: '/v1/auth/me',
        operationId: 'getAuthenticatedUser',
        summary: 'Get current user',
        description: 'Retrieve profile of currently authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or missing token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'hint', type: 'string', example: 'Provide a valid authentication token'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                ref: '#/components/schemas/ErrorResponse'
            ),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        try {
            /** @var ?UserModel $user */
            $user = $request->user();
            if (! $user) {
                Log::warning('Me endpoint accessed without authentication');

                return response()->json([
                    'message' => __('auth.unauthorized'),
                    'hint' => __('auth.hint_provide_valid_token'),
                ], 401);
            }

            $entity = $this->userService->getUserById($user->id);

            return response()->json([
                'data' => new UserResource($entity),
                'message' => __('auth.me_retrieved'),
            ]);
        } catch (Throwable $e) {
            Log::error('AuthController@me error', [
                'user_id' => optional($request->user())->id,
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
