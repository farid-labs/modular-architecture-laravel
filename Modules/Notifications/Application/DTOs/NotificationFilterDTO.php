<?php

namespace Modules\Notifications\Application\DTOs;

use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationType;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * DTO: NotificationFilterDTO
 *
 * Represents filtering criteria for querying notifications.
 *
 * Responsibilities:
 * - Encapsulate filter parameters from external sources (e.g. HTTP request).
 * - Convert raw input into strongly-typed domain enums.
 * - Provide safe array serialization for query builders.
 *
 * This DTO must remain free of business logic.
 */
class NotificationFilterDTO extends DataTransferObject
{
    /** Filter by notification type */
    public ?NotificationType $type = null;

    /** Filter by notification category */
    public ?NotificationCategory $category = null;

    /** Whether to return only unread notifications */
    public ?bool $unreadOnly = null;

    /** Maximum number of results */
    public ?int $limit = null;

    /** Filter start date (ISO string expected) */
    public ?string $startDate = null;

    /** Filter end date (ISO string expected) */
    public ?string $endDate = null;

    /**
     * Create DTO instance from request payload.
     *
     * Safely converts string values into domain enums.
     */
    public static function fromRequest(array $data): self
    {
        return new self([
            'type' => isset($data['type'])
                ? NotificationType::tryFrom($data['type'])
                : null,

            'category' => isset($data['category'])
                ? NotificationCategory::tryFrom($data['category'])
                : null,

            'unreadOnly' => isset($data['unread_only'])
                ? filter_var($data['unread_only'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                : null,

            'limit' => isset($data['limit'])
                ? max(1, (int) $data['limit'])
                : null,

            'startDate' => $data['start_date'] ?? null,
            'endDate'   => $data['end_date'] ?? null,
        ]);
    }

    /**
     * Convert DTO into clean array representation.
     *
     * Notes:
     * - Preserves boolean false values.
     * - Removes only null values.
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'type'        => $this->type?->value,
                'category'    => $this->category?->value,
                'unread_only' => $this->unreadOnly,
                'limit'       => $this->limit,
                'start_date'  => $this->startDate,
                'end_date'    => $this->endDate,
            ],
            fn($value) => $value !== null
        );
    }
}
