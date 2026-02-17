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

        $workspaceId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:100', 'min:3'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                \Illuminate\Validation\Rule::unique('workspaces', 'slug')
                    ->whereNull('deleted_at')
                    ->ignore($workspaceId),
            ],
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
            'name.max' => __('workspaces.name_max'),
            'name.min' => __('workspaces.name_min'),
            'description.max' => __('workspaces.description_max'),
            'status.in' => __('workspaces.invalid_status'),
            'slug.regex' => __('workspaces.slug_invalid_format'),
            'slug.unique' => __('workspaces.slug_taken'),
        ];
    }
}
