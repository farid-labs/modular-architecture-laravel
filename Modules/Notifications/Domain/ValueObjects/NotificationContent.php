<?php

namespace Modules\Notifications\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: NotificationContent
 */
final readonly class NotificationContent
{
    private const MAX_TITLE_LENGTH = 100;

    private const MAX_BODY_LENGTH = 500;

    public function __construct(
        private string $title,
        private string $body,
        private ?string $actionLabel = null,
        private ?string $actionUrl = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        // Title validation
        if (mb_strlen($this->title) > self::MAX_TITLE_LENGTH) {
            throw new InvalidArgumentException(
                __('notifications.errors.title_too_long', ['max' => self::MAX_TITLE_LENGTH])
            );
        }

        // Body validation
        if (mb_strlen($this->body) > self::MAX_BODY_LENGTH) {
            throw new InvalidArgumentException(
                __('notifications.errors.body_too_long', ['max' => self::MAX_BODY_LENGTH])
            );
        }

        //  URL validation - must be valid if provided
        if ($this->actionUrl !== null && $this->actionUrl !== '') {
            // Trim only for validation check, but preserve original value
            $trimmedUrl = trim($this->actionUrl);
            if (! filter_var($trimmedUrl, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException(
                    __('notifications.errors.invalid_url')
                );
            }
        }
    }

    public function title(): string
    {
        return $this->title;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function actionLabel(): ?string
    {
        return $this->actionLabel;
    }

    public function actionUrl(): ?string
    {
        return $this->actionUrl;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'action_label' => $this->actionLabel,
            'action_url' => $this->actionUrl,
        ];
    }
}
