<?php

namespace Modules\Notifications\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Application\DTOs\NotificationFilterDTO;
use Modules\Notifications\Application\DTOs\SendNotificationDTO;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Presentation\Requests\SendNotificationRequest;
use Modules\Notifications\Presentation\Resources\NotificationResource;
use Modules\Workspace\Domain\Exceptions\AuthorizationException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[OA\Tag(name: 'Notifications', description: 'Manage user notifications')]
class NotificationController extends Controller
{
    public function __construct(private NotificationService $service) {}

    /**
     * List notifications for authenticated user with optional filters.
     *
     * Returns a paginated collection of notifications for the authenticated user.
     * Supports filtering by type, category, read status, and date range.
     *
     * @param  Request  $request  The HTTP request containing authentication token and query parameters
     * @return JsonResponse JSON response containing notification collection with pagination metadata
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws AuthorizationException If user lacks permission to view notifications
     */
    #[OA\Get(
        path: '/notifications',
        operationId: 'listUserNotifications',
        summary: 'Retrieve list of notifications',
        description: 'Retrieve all notifications for the authenticated user with filtering and pagination support. 
    Results are ordered by creation date (newest first).',
        security: [['bearerAuth' => []]],
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: false,
                description: 'Filter by notification type',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['info', 'success', 'warning', 'error', 'system']
                )
            ),
            new OA\Parameter(
                name: 'category',
                in: 'query',
                required: false,
                description: 'Filter by notification category',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['system', 'user', 'workspace', 'project', 'task', 'security']
                )
            ),
            new OA\Parameter(
                name: 'unread_only',
                in: 'query',
                required: false,
                description: 'Filter to show only unread notifications',
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
            new OA\Parameter(
                name: 'start_date',
                in: 'query',
                required: false,
                description: 'Filter notifications from this date (ISO 8601 format)',
                schema: new OA\Schema(type: 'string', format: 'date-time')
            ),
            new OA\Parameter(
                name: 'end_date',
                in: 'query',
                required: false,
                description: 'Filter notifications until this date (ISO 8601 format)',
                schema: new OA\Schema(type: 'string', format: 'date-time')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number for pagination',
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Number of items per page',
                schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notifications retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data', 'meta', 'message'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            description: 'Collection of notification resources',
                            items: new OA\Items(ref: '#/components/schemas/NotificationResource')
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            description: 'Pagination metadata',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 42),
                                new OA\Property(property: 'has_more', type: 'boolean', example: true),
                            ]
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            description: 'Success message',
                            example: 'Notifications retrieved successfully'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or missing authentication token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User lacks permission to view notifications',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Forbidden'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Server error'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        // Retrieve authenticated user from request context
        // Throws UnauthorizedHttpException if no valid token provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Build filter DTO from query parameters
        $filters = NotificationFilterDTO::fromRequest([
            'type' => $request->query('type'),
            'category' => $request->query('category'),
            'unread_only' => filter_var($request->query('unread_only', false), FILTER_VALIDATE_BOOLEAN),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
        ]);

        // Extract pagination parameters with defaults
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);

        // Validate pagination parameters
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));

        // Retrieve notifications from service layer
        // Service validates user is authorized to view notifications
        $notifications = $this->service->getUserNotifications(
            $user->id,
            $filters,
            $page,
            $perPage
        );

        // Calculate pagination metadata
        $total = count($notifications);
        $hasMore = $total === $perPage; // Simplified - adjust based on actual pagination logic

        // Return formatted JSON response with notification collection
        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => $hasMore,
            ],
            'message' => __('notifications.retrieved'),
        ]);
    }

    /**
     * Get the count of unread notifications for authenticated user.
     *
     * Returns the total number of unread notifications for the currently authenticated user.
     * This endpoint is optimized for performance and is commonly used for badge counters
     * in UI components (e.g., notification bell icons).
     *
     * **Use Cases:**
     * - Display notification badge count in navigation bar
     * - Poll for new notifications without fetching full list
     * - Determine if user should be prompted to check notifications
     *
     * **Performance:**
     * - Uses database COUNT query (no full record retrieval)
     * - Recommended polling interval: 30-60 seconds
     * - Consider caching for high-traffic applications
     *
     * **Authorization:**
     * - Requires valid Bearer token (Sanctum authentication)
     * - Returns count only for the authenticated user
     * - Excludes soft-deleted and read notifications
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @return JsonResponse JSON response with unread count and success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \Throwable If database query fails
     *
     * @see NotificationController::index() For full notification list
     * @see NotificationController::markAsRead() For marking notifications as read
     */
    #[OA\Get(
        path: '/notifications/unread-count',
        operationId: 'getUnreadNotificationCount',
        summary: 'Get unread notifications count',
        description: 'Returns the total number of unread notifications for the authenticated user. 
        This endpoint is optimized for performance and is commonly used for UI badge counters.',
        security: [['bearerAuth' => []]],
        tags: ['Notifications'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Unread count retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data', 'message'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            required: ['unread_count'],
                            properties: [
                                new OA\Property(
                                    property: 'unread_count',
                                    type: 'integer',
                                    format: 'int32',
                                    minimum: 0,
                                    example: 5,
                                    description: 'Total number of unread notifications'
                                ),
                            ],
                            description: 'Response data container'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Unread count retrieved successfully',
                            description: 'Success message'
                        ),
                    ],
                    example: [
                        'data' => [
                            'unread_count' => 5,
                        ],
                        'message' => 'Unread count retrieved successfully',
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or missing authentication token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Unauthenticated.'
                        ),
                    ],
                    example: [
                        'message' => 'Unauthenticated.',
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error - Database query failed',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Failed to retrieve unread count. Please try again later.'
                        ),
                    ],
                    example: [
                        'message' => 'Failed to retrieve unread count. Please try again later.',
                    ]
                )
            ),
        ]
    )]
    public function unreadCount(Request $request): JsonResponse
    {
        // Retrieve authenticated user from request context
        // Throws UnauthorizedHttpException if no valid token provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Get unread count from service layer
        // Service validates user exists and performs optimized COUNT query
        $count = $this->service->getUnreadCount($user->id);

        // Return JSON response with count and success message
        return response()->json([
            'data' => ['unread_count' => $count],
            'message' => __('notifications.unread_count_retrieved'),
        ]);
    }

    /**
     * Send a new notification to a user via specified channels.
     *
     * This endpoint allows authenticated users to send notifications to other users
     * through multiple delivery channels (database, email, SMS, push).
     * The notification is created, persisted, and queued for asynchronous delivery.
     *
     * **Authorization:**
     * - Requires valid Bearer token (Sanctum authentication)
     * - User must have permission to send notifications
     * - Recipient must exist in the system
     *
     * **Business Rules:**
     * - Title maximum length: 100 characters
     * - Message maximum length: 500 characters
     * - At least one delivery channel must be specified
     * - Recipient user must exist in the database
     *
     * **Delivery Channels:**
     * - `database`: Stores notification in database (default)
     * - `email`: Sends notification via email
     * - `sms`: Sends notification via SMS (Vonage)
     * - `push`: Sends push notification via broadcast
     *
     * @param  SendNotificationRequest  $request  The validated notification request
     * @return JsonResponse JSON response containing created notification resource
     *
     * @throws \Illuminate\Auth\AuthenticationException If user is not authenticated
     * @throws \Illuminate\Validation\ValidationException If request validation fails
     * @throws \InvalidArgumentException If recipient user does not exist
     *
     * @see SendNotificationRequest For request validation rules
     * @see NotificationResource For response schema
     * @see \Modules\Notifications\Application\Services\NotificationService
     */
    #[OA\Post(
        path: '/notifications/send',
        operationId: 'sendNotification',
        summary: 'Send a new notification',
        description: 'Create and dispatch a notification to a user through one or more delivery channels. 
        The notification is persisted in the database and queued for asynchronous delivery via the specified channels.',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Notification details including recipient, content, and delivery channels',
            content: new OA\JsonContent(
                required: ['recipient_id', 'type', 'title', 'message'],
                properties: [
                    new OA\Property(
                        property: 'recipient_id',
                        type: 'integer',
                        format: 'int64',
                        description: 'ID of the user who will receive the notification',
                        example: 1,
                        minimum: 1
                    ),
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        description: 'Notification type classification',
                        enum: ['info', 'success', 'warning', 'error', 'system'],
                        example: 'info'
                    ),
                    new OA\Property(
                        property: 'priority',
                        type: 'string',
                        description: 'Notification priority level (determines delivery urgency)',
                        enum: ['low', 'medium', 'high', 'urgent'],
                        example: 'medium',
                        default: 'medium'
                    ),
                    new OA\Property(
                        property: 'category',
                        type: 'string',
                        description: 'Notification category for grouping and filtering',
                        enum: ['system', 'user', 'workspace', 'project', 'task', 'security'],
                        example: 'system',
                        default: 'system'
                    ),
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        description: 'Notification title (max 100 characters)',
                        example: 'New Message Received',
                        minLength: 1,
                        maxLength: 100
                    ),
                    new OA\Property(
                        property: 'message',
                        type: 'string',
                        description: 'Notification message body (max 500 characters)',
                        example: 'You have received a new message from John Doe.',
                        minLength: 1,
                        maxLength: 500
                    ),
                    new OA\Property(
                        property: 'channels',
                        type: 'array',
                        description: 'Delivery channels for the notification',
                        items: new OA\Items(
                            type: 'string',
                            enum: ['database', 'email', 'sms', 'push']
                        ),
                        example: ['database', 'email'],
                        minItems: 1,
                        default: ['database']
                    ),
                    new OA\Property(
                        property: 'action_url',
                        type: 'string',
                        format: 'uri',
                        description: 'Optional URL for notification action (e.g., link to related resource)',
                        example: 'https://example.com/messages/123',
                        nullable: true
                    ),
                    new OA\Property(
                        property: 'action_label',
                        type: 'string',
                        description: 'Optional label for the action button',
                        example: 'View Message',
                        nullable: true,
                        maxLength: 50
                    ),
                    new OA\Property(
                        property: 'metadata',
                        type: 'object',
                        description: 'Optional additional metadata for custom processing',
                        example: ['message_id' => 123, 'conversation_id' => 456],
                        nullable: true,
                        additionalProperties: true
                    ),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Notification created successfully and queued for delivery',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/NotificationResource',
                            description: 'Created notification resource'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            description: 'Success message',
                            example: 'Notification created successfully'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or missing authentication token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Unauthenticated.'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User lacks permission to send notifications',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'You do not have permission to send notifications.'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Recipient user does not exist',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'User with ID 999 does not exist.'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Unprocessable Entity - Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'The given data was invalid.'
                        ),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            description: 'Field-specific validation errors',
                            example: [
                                'recipient_id' => ['The selected recipient_id is invalid.'],
                                'title' => ['The title must not be greater than 100 characters.'],
                                'channels' => ['The channels field must have at least 1 item.'],
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error - Notification delivery failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Failed to send notification. Please try again later.'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function send(SendNotificationRequest $request): JsonResponse
    {
        try {
            // Get validated request data
            $validated = $request->validated();

            // Create notification DTO with validated data
            $dto = SendNotificationDTO::forUser(
                userId: $validated['recipient_id'],
                type: NotificationType::from($validated['type']),
                title: $validated['title'],
                message: $validated['message'],
                channels: array_map(
                    fn ($c) => NotificationChannel::from($c),
                    $validated['channels'] ?? ['database']
                ),
                priority: NotificationPriority::from($validated['priority'] ?? 'medium'),
                category: NotificationCategory::from($validated['category'] ?? 'system'),
                actionUrl: $validated['action_url'] ?? null,
                actionLabel: $validated['action_label'] ?? null,
                metadata: $validated['metadata'] ?? null,
            );

            // Send notification via service layer
            $entity = $this->service->sendNotification($dto);

            // Return 201 Created with notification resource
            return response()->json([
                'data' => new NotificationResource($entity),
                'message' => __('notifications.created'),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            // Handle user not found error
            Log::channel('domain')->warning('Invalid notification recipient', [
                'recipient_id' => $request->input('recipient_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => __('notifications.errors.user_not_found', [
                    'id' => $request->input('recipient_id'),
                ]) ?: $e->getMessage(),
                'error_code' => 'recipient_not_found',
            ], 404);
        } catch (\Throwable $e) {
            // Handle unexpected errors
            Log::channel('domain')->error('Failed to send notification', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'message' => __('errors.server_error'),
                'error_code' => 'notification_send_failed',
            ], 500);
        }
    }

    /**
     * Mark a specific notification as read.
     *
     * Updates the read status of a notification for the authenticated user.
     * Only the notification owner can mark their notifications as read.
     *
     * @param  string  $id  The notification UUID
     * @param  Request  $request  The HTTP request containing authentication token
     * @return JsonResponse JSON response with success message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws AuthorizationException If user doesn't own the notification
     */
    #[OA\Patch(
        path: '/notifications/{id}/read',
        operationId: 'markNotificationAsRead',
        summary: 'Mark a notification as read',
        description: 'Updates the read status of a specific notification for the authenticated user. 
    Only the notification owner can perform this action.',
        security: [['bearerAuth' => []]],
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The notification UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid', example: 'notif_abc123')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification marked as read successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Notification marked as read'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or missing authentication token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Notification not found or user doesn\'t own this notification',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Notification not found'),
                    ]
                )
            ),
        ]
    )]
    public function markAsRead(string $id, Request $request): JsonResponse
    {
        // Retrieve authenticated user from request
        // Throws UnauthorizedHttpException if no valid token provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        //  Validate UUID format before database query
        if (! $this->isValidUuid($id)) {
            return response()->json([
                'message' => __('notifications.invalid_id_format'),
                'error_code' => 'invalid_uuid',
            ], 400);
        }

        // Mark notification as read via service layer
        // Service validates user owns the notification
        $success = $this->service->markAsRead(notificationId: $id, userId: $user->id);

        // Return 404 if notification not found or user doesn't own it
        if (! $success) {
            return response()->json([
                'message' => __('notifications.not_found_or_unauthorized'),
                'error_code' => 'notification_access_denied',
            ], 404);
        }

        // Return success response
        return response()->json([
            'message' => __('notifications.marked_read'),
        ]);
    }

    /**
     * Mark all notifications as read for authenticated user.
     *
     * Bulk operation to mark all unread notifications as read for the currently
     * authenticated user. This is useful for "mark all as read" functionality
     * in notification centers and reduces UI clutter.
     *
     * **Use Cases:**
     * - Clear notification badge counter
     * - Bulk acknowledgment of notifications
     * - User preference to dismiss all notifications at once
     *
     * **Business Rules:**
     * - Only affects notifications owned by the authenticated user
     * - Excludes already-read notifications from count
     * - Excludes soft-deleted notifications
     * - Returns count of actually modified records
     *
     * **Performance:**
     * - Single database UPDATE query
     * - No cache invalidation required (read status is not cached)
     * - Returns immediately without background processing
     *
     * @param  Request  $request  The HTTP request containing authentication token
     * @return JsonResponse JSON response with count of marked notifications
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws \Throwable If database operation fails
     *
     * @see NotificationController::markAsRead() For marking single notification
     * @see NotificationController::index() For retrieving notification list
     */
    #[OA\Post(
        path: '/notifications/mark-all-read',
        operationId: 'markAllNotificationsAsRead',
        summary: 'Mark all notifications as read',
        description: 'Bulk operation to mark all unread notifications as read for the authenticated user. 
        Returns the count of notifications that were actually updated.',
        security: [['bearerAuth' => []]],
        tags: ['Notifications'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All notifications marked as read successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data', 'message'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            required: ['marked_count'],
                            properties: [
                                new OA\Property(
                                    property: 'marked_count',
                                    type: 'integer',
                                    format: 'int32',
                                    minimum: 0,
                                    example: 5,
                                    description: 'Number of notifications marked as read'
                                ),
                            ],
                            description: 'Response data container'
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'All notifications marked as read',
                            description: 'Success message'
                        ),
                    ],
                    example: [
                        'data' => [
                            'marked_count' => 5,
                        ],
                        'message' => 'All notifications marked as read',
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or missing authentication token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Unauthenticated.'
                        ),
                    ],
                    example: [
                        'message' => 'Unauthenticated.',
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error - Database operation failed',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Failed to mark notifications as read. Please try again later.'
                        ),
                    ],
                    example: [
                        'message' => 'Failed to mark notifications as read. Please try again later.',
                    ]
                )
            ),
        ]
    )]
    public function markAllAsRead(Request $request): JsonResponse
    {
        // Retrieve authenticated user from request
        // Throws UnauthorizedHttpException if no valid token provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Log bulk read operation for audit trail
        Log::channel('domain')->info('Marking all notifications as read', [
            'user_id' => $user->id,
        ]);

        // Execute bulk update via service layer
        // Returns count of actually modified records
        $count = $this->service->markAllAsRead($user->id);

        // Log result for monitoring
        Log::channel('domain')->info('Bulk mark read completed', [
            'user_id' => $user->id,
            'marked_count' => $count,
        ]);

        // Return success response with count
        return response()->json([
            'data' => ['marked_count' => $count],
            'message' => __('notifications.all_marked_read'),
        ]);
    }

    /**
     * Soft-delete a specific notification.
     *
     * Permanently removes a notification from the user's view by setting
     * the deleted_at timestamp. The notification record is retained in the
     * database for audit purposes but hidden from all user queries.
     *
     * **Authorization:**
     * - Only the notification owner can delete their notifications
     * - Admin users may delete any notification (if implemented)
     * - Validates UUID format before database query
     *
     * **Business Rules:**
     * - Soft delete (record retained with deleted_at timestamp)
     * - Cannot delete already-deleted notifications
     * - Cannot delete other users' notifications
     * - Invalid UUID returns 400 Bad Request
     *
     * **Security:**
     * - UUID validation prevents SQL injection
     * - Ownership check prevents unauthorized deletion
     * - Audit log tracks all deletion attempts
     *
     * @param  string  $id  The notification UUID to delete
     * @param  Request  $request  The HTTP request containing authentication token
     * @return JsonResponse JSON response with success or error message
     *
     * @throws UnauthorizedHttpException If user is not authenticated
     * @throws AuthorizationException If user doesn't own the notification
     *
     * @see NotificationController::markAsRead() For marking as read instead
     * @see NotificationController::markAllAsRead() For bulk operations
     */
    #[OA\Delete(
        path: '/notifications/{id}',
        operationId: 'deleteNotification',
        summary: 'Delete a notification',
        description: 'Soft-delete a specific notification by UUID. Only the notification owner 
        can delete their notifications. The record is retained for audit purposes.',
        security: [['bearerAuth' => []]],
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Notification UUID (format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)',
                schema: new OA\Schema(
                    type: 'string',
                    format: 'uuid',
                    pattern: '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
                    example: '550e8400-e29b-41d4-a716-446655440000'
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification deleted successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Notification deleted successfully',
                            description: 'Success message'
                        ),
                    ],
                    example: [
                        'message' => 'Notification deleted successfully',
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Invalid UUID format',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Invalid notification ID format'
                        ),
                        new OA\Property(
                            property: 'error_code',
                            type: 'string',
                            example: 'invalid_uuid'
                        ),
                    ],
                    example: [
                        'message' => 'Invalid notification ID format',
                        'error_code' => 'invalid_uuid',
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or missing authentication token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Unauthenticated.'
                        ),
                    ],
                    example: [
                        'message' => 'Unauthenticated.',
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Notification does not exist or user lacks permission',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Notification not found'
                        ),
                    ],
                    example: [
                        'message' => 'Notification not found',
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error - Deletion operation failed',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Failed to delete notification. Please try again later.'
                        ),
                    ],
                    example: [
                        'message' => 'Failed to delete notification. Please try again later.',
                    ]
                )
            ),
        ]
    )]
    public function destroy(string $id, Request $request): JsonResponse
    {
        // Retrieve authenticated user from request
        // Throws UnauthorizedHttpException if no valid token provided
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        // Log deletion attempt for audit trail
        Log::channel('domain')->info('Notification deletion requested', [
            'notification_id' => $id,
            'user_id' => $user->id,
        ]);

        // Validate UUID format before database query
        // Prevents SQL injection and invalid database queries
        if (! $this->isValidUuid($id)) {
            Log::channel('domain')->warning('Invalid UUID format for notification deletion', [
                'notification_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => __('notifications.invalid_id_format'),
                'error_code' => 'invalid_uuid',
            ], 400);
        }

        // Execute soft delete via service layer
        // Service validates ownership and performs deletion
        $success = $this->service->deleteNotification(
            notificationId: $id,
            userId: $user->id
        );

        // Handle deletion result
        if (! $success) {
            // Log failed deletion attempt (could be not found or unauthorized)
            Log::channel('domain')->warning('Notification deletion failed', [
                'notification_id' => $id,
                'user_id' => $user->id,
                'reason' => 'not_found_or_unauthorized',
            ]);

            return response()->json([
                'message' => __('notifications.not_found'),
            ], 404);
        }

        // Log successful deletion for audit trail
        Log::channel('domain')->info('Notification deleted successfully', [
            'notification_id' => $id,
            'user_id' => $user->id,
        ]);

        // Return success response
        return response()->json([
            'message' => __('notifications.deleted'),
        ]);
    }

    /**
     * Determine whether the given string is a valid UUID (Universally Unique Identifier).
     *
     * Validation Rules:
     * - Must follow canonical 8-4-4-4-12 hexadecimal format
     * - Hyphen-separated segments
     * - Case-insensitive (supports upper and lower hex characters)
     *
     * Accepted format example:
     *   550e8400-e29b-41d4-a716-446655440000
     *
     * Implementation Notes:
     * - Uses strict regex match anchored with ^ and $
     * - Returns true only when preg_match result equals 1
     * - Does NOT validate UUID version or variant bits (v1–v8)
     * - Only validates structural format
     *
     * Performance:
     * - O(n) regex evaluation
     * - Safe for repeated calls
     *
     * Security:
     * - Prevents malformed UUID injection
     * - Should be used before DB queries or entity resolution
     *
     * @param  string  $uuid  Candidate UUID string
     * @return bool True if structurally valid UUID format, false otherwise
     *
     * @phpstan-assert-if-true non-empty-string $uuid
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid
        ) === 1;
    }
}
