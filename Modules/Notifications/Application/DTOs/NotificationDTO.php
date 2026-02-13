<?php

namespace Modules\Notifications\Application\DTOs;

use Modules\Notifications\Domain\Enums\NotificationType;
use Spatie\DataTransferObject\DataTransferObject;

class NotificationDTO extends DataTransferObject
{
    public NotificationType $type;

    public string $title;

    public string $message;

    /** @var array<string, mixed>|null */
    public ?array $data = null;

    public ?string $action_url = null;

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $data['type'] = NotificationType::from($data['type']);

        return new self($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'action_url' => $this->action_url,
        ];
    }
}
