<?php

namespace Modules\Notifications\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: NotificationContent
 *
 * Represents the immutable content of a notification.
 *
 * Responsibilities:
 * - Encapsulates notification title, body, and optional action data.
 * - Enforces domain validation rules.
 * - Guarantees data integrity across the domain layer.
 *
 * Design Notes:
 * - Immutable (readonly).
 * - Throws domain-level exceptions when invalid.
 * - Prevents invalid state construction.
 */
final readonly class NotificationContent
{
    /**
     * Maximum allowed characters for notification title.
     */
    private const MAX_TITLE_LENGTH = 100;

    /**
     * Maximum allowed characters for notification body.
     */
    private const MAX_BODY_LENGTH = 500;

    /**
     * Create a new NotificationContent value object.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        private string $title,
        private string $body,
        private ?string $actionLabel = null,
        private ?string $actionUrl = null
    ) {
        $this->validate();
    }

    /**
     * Validate content integrity.
     *
     * Business Rules:
     * - Title must not exceed defined max length.
     * - Body must not exceed defined max length.
     * - Action URL must be a valid URL when provided.
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if (mb_strlen($this->title) > self::MAX_TITLE_LENGTH) {
            throw new InvalidArgumentException(
                __('notification.validation.title_max', [
                    'max' => self::MAX_TITLE_LENGTH,
                ])
            );
        }

        if (mb_strlen($this->body) > self::MAX_BODY_LENGTH) {
            throw new InvalidArgumentException(
                __('notification.validation.body_max', [
                    'max' => self::MAX_BODY_LENGTH,
                ])
            );
        }

        if (
            $this->actionUrl !== null &&
            ! filter_var($this->actionUrl, FILTER_VALIDATE_URL)
        ) {
            throw new InvalidArgumentException(
                __('notification.validation.invalid_action_url')
            );
        }
    }

    /**
     * Get notification title.
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Get notification body.
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * Get action button label (if any).
     */
    public function actionLabel(): ?string
    {
        return $this->actionLabel;
    }

    /**
     * Get action URL (if any).
     */
    public function actionUrl(): ?string
    {
        return $this->actionUrl;
    }

    /**
     * Convert the value object into an array.
     *
     * Useful for persistence or serialization.
     */
    public function toArray(): array
    {
        return [
            'title'        => $this->title,
            'body'         => $this->body,
            'action_label' => $this->actionLabel,
            'action_url'   => $this->actionUrl,
        ];
    }
}
