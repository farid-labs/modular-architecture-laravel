<?php

namespace Modules\Workspace\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Always allow for now; authorization handled elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * Defines required fields and constraints for creating a project.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'min:3'], // Project name, required, 3-100 characters
            'description' => ['nullable', 'string', 'max:1000'], // Optional description, max 1000 characters
            'status' => ['sometimes', 'in:active,completed,archived'], // Optional status with allowed values
        ];
    }

    /**
     * Get custom messages for validator errors.
     * Provides user-friendly messages for each validation failure.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Project name is required.',
            'name.min' => 'Project name must be at least 3 characters.',
            'name.max' => 'Project name must not exceed 100 characters.',
            'description.max' => 'Description must not exceed 1000 characters.',
            'status.in' => 'Invalid project status.',
        ];
    }
}
