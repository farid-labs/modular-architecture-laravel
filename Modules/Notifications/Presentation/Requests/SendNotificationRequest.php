<?php

namespace Modules\Notifications\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Notifications\Domain\Enums\NotificationCategory;
use Modules\Notifications\Domain\Enums\NotificationChannel;
use Modules\Notifications\Domain\Enums\NotificationPriority;
use Modules\Notifications\Domain\Enums\NotificationType;

/**
 * Form request for sending a new notification.
 * Validates required fields, optional fields, and enums.
 */
class SendNotificationRequest extends FormRequest
{
    /**
     * Authorize all users to send notifications.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for notification request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::enum(NotificationType::class)],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
            'category' => ['sometimes', Rule::enum(NotificationCategory::class)],
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:500'],
            'channels' => ['sometimes', 'array'],
            'channels.*' => ['string', Rule::enum(NotificationChannel::class)],
            'action_url' => ['nullable', 'url', 'max:255'],
            'action_label' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Custom validation messages (translation-ready).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient_id.required' => __('notifications.validation.recipient_required'),
            'recipient_id.exists' => __('notifications.validation.recipient_not_found'),
            'type.required' => __('notifications.validation.type_required'),
            'type.enum' => __('notifications.validation.type_invalid'),
            'title.required' => __('notifications.validation.title_required'),
            'title.max' => __('notifications.validation.title_max'),
            'message.required' => __('notifications.validation.message_required'),
            'message.max' => __('notifications.validation.message_max'),
            'channels.*.enum' => __('notifications.validation.channel_invalid'),
            'action_url.url' => __('notifications.validation.action_url_invalid'),
        ];
    }
}
