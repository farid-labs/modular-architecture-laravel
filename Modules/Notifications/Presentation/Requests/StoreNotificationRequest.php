<?php

namespace Modules\Notifications\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(\Modules\Notifications\Domain\Enums\NotificationType::class)],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'data' => ['nullable', 'array'],
            'action_url' => ['nullable', 'url'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', Rule::enum(\Modules\Notifications\Domain\Enums\NotificationChannel::class)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Notification type is required',
            'type.enum' => 'Invalid notification type',
            'title.required' => 'Notification title is required',
            'title.max' => 'Notification title must not exceed 255 characters',
            'message.required' => 'Notification message is required',
            'channels.*.enum' => 'Invalid notification channel',
        ];
    }
}
