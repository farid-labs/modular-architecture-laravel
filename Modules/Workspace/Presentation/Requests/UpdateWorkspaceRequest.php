<?php

namespace Modules\Workspace\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:100', 'min:3'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'status' => ['sometimes', 'in:active,inactive,suspended'],
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
            'name.max' => 'Workspace name must not exceed 100 characters',
            'name.min' => 'Workspace name must be at least 3 characters',
            'description.max' => 'Description must not exceed 1000 characters',
            'status.in' => 'Invalid workspace status',
        ];
    }
}
