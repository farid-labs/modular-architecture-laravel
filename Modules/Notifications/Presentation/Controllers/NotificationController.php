<?php

namespace Modules\Notifications\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notifications\Application\DTOs\NotificationFilterDTO;
use Modules\Notifications\Application\DTOs\SendNotificationDTO;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;
use Modules\Notifications\Presentation\Requests\SendNotificationRequest;
use Modules\Notifications\Presentation\Resources\NotificationResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[OA\Tag(name: 'Notifications', description: 'Manage user notifications')]
class NotificationController extends Controller
{
    public function __construct(private NotificationService $service) {}

    /**
     * List notifications for authenticated user with optional filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/notifications',
        summary: 'Retrieve list of notifications',
        description: 'Retrieve all notifications for the authenticated user with filtering and pagination',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        $filters = NotificationFilterDTO::fromRequest([
            'type' => $request->query('type'),
            'category' => $request->query('category'),
            'unread_only' => filter_var($request->query('unread_only', false), FILTER_VALIDATE_BOOLEAN),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
        ]);

        $notifications = $this->service->getUserNotifications(
            userId: $user->id,
            filters: $filters,
            page: (int) $request->query('page', 1),
            perPage: (int) $request->query('per_page', 15)
        );

        return response()->json([
            'data' => $notifications,
            'meta' => [
                'current_page' => $notifications->currentPage() ?? 1,
                'per_page' => $notifications->perPage() ?? 15,
                'total' => count($notifications),
                'has_more' => false,
            ],
            'message' => __('notifications.retrieved'),
        ]);
    }

    /**
     * Get the count of unread notifications for authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/notifications/unread-count',
        summary: 'Get unread notifications count',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        $count = $this->service->getUnreadCount($user->id);

        return response()->json([
            'data' => ['unread_count' => $count],
            'message' => __('notifications.unread_count_retrieved'),
        ]);
    }

    /**
     * Send a new notification to a user via specified channels.
     *
     * @param SendNotificationRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/notifications/send',
        summary: 'Send a new notification',
        description: 'Send a notification to a user through specified channels',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = SendNotificationDTO::forUser(
            userId: $validated['recipient_id'],
            type: NotificationType::from($validated['type']),
            title: $validated['title'],
            message: $validated['message'],
            channels: array_map(fn($c) => NotificationChannel::from($c), $validated['channels'] ?? ['database']),
            priority: NotificationPriority::from($validated['priority'] ?? 'medium'),
            category: NotificationCategory::from($validated['category'] ?? 'system'),
            actionUrl: $validated['action_url'] ?? null,
            actionLabel: $validated['action_label'] ?? null,
            metadata: $validated['metadata'] ?? null,
        );

        $entity = $this->service->sendNotification($dto);

        return response()->json([
            'data' => new NotificationResource($entity),
            'message' => __('notifications.created'),
        ], 201);
    }

    /**
     * Mark a specific notification as read.
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Patch(
        path: '/api/v1/notifications/{id}/read',
        summary: 'Mark a notification as read',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    public function markAsRead(string $id, Request $request): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        $success = $this->service->markAsRead(notificationId: $id, userId: $user->id);

        if (!$success) {
            return response()->json(['message' => __('notifications.not_found')], 404);
        }

        return response()->json(['message' => __('notifications.marked_read')]);
    }

    /**
     * Mark all notifications as read for authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/notifications/mark-all-read',
        summary: 'Mark all notifications as read',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        $count = $this->service->markAllAsRead($user->id);

        return response()->json([
            'data' => ['marked_count' => $count],
            'message' => __('notifications.all_marked_read'),
        ]);
    }

    /**
     * Soft-delete a specific notification.
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/v1/notifications/{id}',
        summary: 'Delete a notification',
        security: [['bearerAuth' => []]],
        tags: ['Notifications']
    )]
    public function destroy(string $id, Request $request): JsonResponse
    {
        $user = $request->user() ?? throw new UnauthorizedHttpException('Unauthorized');

        $success = $this->service->deleteNotification(notificationId: $id, userId: $user->id);

        if (!$success) {
            return response()->json(['message' => __('notifications.not_found')], 404);
        }

        return response()->json(['message' => __('notifications.deleted')]);
    }
}
