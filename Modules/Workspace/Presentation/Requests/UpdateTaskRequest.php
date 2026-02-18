<?php

namespace Modules\Workspace\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for updating a task.
 */
class UpdateTaskRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255', 'min:3'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'status' => ['sometimes', 'in:pending,in_progress,completed,blocked,cancelled'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'due_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
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
            'title.min' => 'Task title must be at least 3 characters.',
            'title.max' => 'Task title must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 2000 characters.',
            'assigned_to.exists' => 'Assigned user does not exist.',
            'status.in' => 'Invalid task status.',
            'priority.in' => 'Invalid priority value.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
        ];
    }
}
