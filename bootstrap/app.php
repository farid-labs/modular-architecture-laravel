<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Enable Laravel’s default stateful API behaviour for Sanctum (SPA)
        $middleware->statefulApi();

        // Define web group:
        $middleware->group('web', [
            EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
        ]);

        // Define API group:
        $middleware->group('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            'auth:sanctum',
            SubstituteBindings::class,
        ]);

        // Middleware aliases:
        $middleware->alias([
            'auth' => Authenticate::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'throttle' => ThrottleRequests::class,
        ]);

        Authenticate::redirectUsing(function ($request) {
            // For all API requests, throw the exception directly (no redirect)
            if ($request->is('api/*', 'v1/*', 'v2/*')) {
                return null; // Returning null causes an AuthenticationException to be thrown
            }

            // For non-API requests (if any), redirect to the login route
            return app('router')->has('login') ? route('login') : null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 🔑 Handle rate limiting with JSON response
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*', 'v1/*', 'v2/*')) {
                return response()->json([
                    'message' => __('errors.too_many_requests'),
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
                ], 429);
            }
        });

        // 🔑 Handle method not allowed for API requests
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*', 'v1/*', 'v2/*')) {
                $allowHeader = $e->getHeaders()['Allow'] ?? '';

                return response()->json([
                    'message' => __('errors.method_not_allowed'),
                    'hint' => __('errors.hint_method', [
                        'methods' => $allowHeader,
                    ]),
                    'allowed_methods' => explode(', ', $allowHeader),
                ], 405);
            }
        });

        // 🔑 Handle not found endpoints for API requests
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*', 'v1/*', 'v2/*')) {
                return response()->json([
                    'message' => __('errors.endpoint_not_found'),
                    'hint' => __('errors.hint_check_url'),
                ], 404);
            }
        });

        // 🔑 Handle model not found exceptions for API requests
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*', 'v1/*', 'v2/*')) {
                // Determine model type for specific messages
                $model = class_basename($e->getModel());

                return response()->json([
                    'message' => match ($model) {
                        'UserModel' => __('users.not_found'),
                        'WorkspaceModel' => __('workspaces.not_found'),
                        default => __('errors.resource_not_found', ['resource' => $model])
                    },
                ], 404);
            }
        });

        // 🔑 Handle validation exceptions with structured errors
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*', 'v1/*', 'v2/*')) {
                return response()->json([
                    'message' => __('errors.validation_failed'),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*', 'v1/*', 'v2/*')) {

                // Log unauthenticated API access attempts
                Log::warning('Unauthenticated API request', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'path' => $request->path(),
                ]);

                // Return a standardized JSON response for API authentication failures
                return response()->json([
                    'message' => __('auth.unauthenticated'),
                    'hint' => __('auth.hint_unauthenticated'),
                    'documentation' => config('app.debug')
                        ? 'https://laravel.com/docs/sanctum#api-token-authentication'
                        : null, // Include documentation link only in debug mode
                ], 401);
            }

            // For non-API requests, preserve Laravel's default behavior
            return null;
        });
    })->create();
