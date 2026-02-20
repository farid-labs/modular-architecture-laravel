<?php

namespace Modules\Workspace\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    /**
     * @return array<string, array|string>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Comment is required.',
            'comment.min' => 'Comment must be at least 3 characters.',
            'comment.max' => 'Comment must not exceed 2000 characters.',
        ];
    }
}
