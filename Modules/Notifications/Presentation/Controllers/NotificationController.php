<?php

namespace Modules\Notifications\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Application\DTOs\NotificationDTO;
use Modules\Notifications\Presentation\Requests\StoreNotificationRequest;
use Modules\Notifications\Presentation\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $notifications = $this->notificationService->getAllNotifications($user->id);

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'message' => 'Notifications retrieved successfully'
        ]);
    }

    /**
     * Get unread notifications for the authenticated user
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $notifications = $this->notificationService->getUnreadNotifications($user->id);

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'message' => 'Unread notifications retrieved successfully'
        ]);
    }

    /**
     * Send a new notification
     */
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $notificationDTO = NotificationDTO::fromArray($request->validated());

        $channels = array_map(
            fn($c) => \Modules\Notifications\Domain\Enums\NotificationChannel::from($c),
            $request->input('channels', ['database'])
        );

        $this->notificationService->sendNotification(
            $user->id,
            $notificationDTO,
            $channels
        );

        return response()->json([
            'message' => 'Notification sent successfully'
        ], 201);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(int $id, Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->notificationService->markAsRead($id, $user->id);

        return response()->json([
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->notificationService->deleteNotification($id, $user->id);

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }
}
