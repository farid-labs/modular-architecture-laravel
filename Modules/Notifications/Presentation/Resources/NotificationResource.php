<?php

namespace Modules\Notifications\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notifications\Domain\Entities\NotificationEntity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'NotificationResource',
    type: 'object',
    description: 'Notification resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: 'notif_abc123'),
        new OA\Property(property: 'type', type: 'string', enum: ['info', 'success', 'warning', 'error'], example: 'info'),
        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], example: 'medium'),
        new OA\Property(property: 'category', type: 'string', enum: ['system', 'user', 'workspace', 'project', 'task', 'security'], example: 'system'),
        new OA\Property(property: 'title', type: 'string', example: 'New Message'),
        new OA\Property(property: 'message', type: 'string', example: 'Your notification content'),
        new OA\Property(property: 'action_url', type: 'string', nullable: true, format: 'uri'),
        new OA\Property(property: 'action_label', type: 'string', nullable: true),
        new OA\Property(property: 'is_read', type: 'boolean', example: false),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'badge_color', type: 'string', example: 'yellow'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class NotificationResource extends JsonResource
{
    /**
     * Transform the notification entity into a JSON-friendly array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Ensure resource is a domain entity
        if (! $this->resource instanceof NotificationEntity) {
            /** @var array<string, mixed> */
            return parent::toArray($request);
        }

        $content = $this->resource->getContent();

        return [
            'id' => $this->resource->getId(),
            'type' => $this->resource->getType()->value,
            'priority' => $this->resource->getPriority()->value,
            'category' => $this->resource->getCategory()->value,
            'title' => $content->title(),
            'message' => $content->body(),
            'action_url' => $content->actionUrl(),
            'action_label' => $content->actionLabel(),
            'is_read' => $this->resource->isRead(),
            'is_active' => $this->resource->isActive(),
            'badge_color' => $this->resource->getPriority()->badgeColor(),
            'metadata' => $this->resource->getMetadata(),
            'created_at' => $this->resource->getCreatedAt()?->toIso8601String(),
            'read_at' => $this->resource->getReadAt()?->toIso8601String(),
        ];
    }
}
