<?php

namespace Modules\Workspace\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreWorkspaceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('workspaces', 'slug')->whereNull('deleted_at'),
            ],
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
            'slug.required' => __('workspaces.slug_required'),
            'slug.regex' => __('workspaces.slug_invalid_format'),
            'slug.unique' => __('workspaces.slug_taken'),
            'name.min' => __('workspaces.name_min'),
            'name.max' => __('workspaces.name_max'),
            'description.max' => __('workspaces.description_max'),
            'status.in' => __('workspaces.invalid_status'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->slug) && ! empty($this->name)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
