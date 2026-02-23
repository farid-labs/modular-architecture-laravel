<?php

namespace Modules\Workspace\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Form Request for storing task attachments.
 *
 * Validates multiple file uploads with the following rules:
 * - Minimum 1 file, maximum 3 files per request
 * - Each file: max 10MB
 * - Allowed types: jpeg, png, gif, webp, pdf
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
class StoreTaskAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller/policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:3'],
            'files.*' => [
                'required',
                'file',
                'max:10240', // 10MB in KB
                'mimes:jpeg,png,gif,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf',
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
            'files.required' => __('workspaces.attachment_required'),
            'files.array' => __('workspaces.files_must_be_array'),
            'files.min' => __('workspaces.attachment_min_count', ['min' => 1]),
            'files.*.required' => __('workspaces.file_required'),
            'files.*.file' => __('workspaces.file_must_be_file'),
            'files.*.max' => __('workspaces.file_size_exceeds_limit', ['max' => '10MB']),
            'files.*.mimes' => __('workspaces.invalid_file_type'),
            'files.*.mimetypes' => __('workspaces.invalid_file_type'),
        ];
    }

    /**
     * Get validated files as UploadedFile array.
     *
     * @return array<int, UploadedFile>
     */
    public function getFiles(): array
    {
        return $this->validated()['files'] ?? [];
    }

    /**
     * Check if request has files.
     */
    public function hasFiles(): bool
    {
        return ! empty($this->getFiles());
    }
}
