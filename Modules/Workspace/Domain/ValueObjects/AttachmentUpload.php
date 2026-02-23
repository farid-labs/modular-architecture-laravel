<?php

namespace Modules\Workspace\Domain\ValueObjects;

use Illuminate\Http\UploadedFile;
use Modules\Workspace\Domain\Exceptions\AuthorizationException;

/**
 * Value object representing a single attachment upload.
 *
 * Encapsulates all file metadata and validation rules:
 * - File integrity validation
 * - MIME type validation
 * - File size validation
 * - File name sanitization
 *
 * Ensures upload data integrity across the domain layer.
 * Immutable by design (readonly class).
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
final readonly class AttachmentUpload
{
    /**
     * Allowed MIME types for attachments.
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
    ];

    /**
     * The uploaded file instance.
     */
    private UploadedFile $file;

    /**
     * The validated file name.
     */
    private FileName $fileName;

    /**
     * The validated file size.
     */
    private FileSize $fileSize;

    /**
     * The file MIME type.
     */
    private string $mimeType;

    /**
     * Create a new AttachmentUpload value object.
     *
     * Validates the uploaded file before instantiation.
     *
     * @param  UploadedFile  $file  The uploaded file instance
     *
     * @throws AuthorizationException If file validation fails
     */
    public function __construct(UploadedFile $file)
    {
        $this->ensureIsValid($file);

        $this->file = $file;
        $this->fileName = new FileName($file->getClientOriginalName());
        $this->fileSize = new FileSize($file->getSize());
        // Provide default value if getMimeType() returns null
        $this->mimeType = $file->getMimeType() ?: 'application/octet-stream';
    }

    /**
     * Validate the uploaded file.
     *
     * Checks for upload errors, file integrity, and MIME type.
     *
     * @param  UploadedFile  $file  The file to validate
     *
     * @throws AuthorizationException If validation fails
     */
    private function ensureIsValid(UploadedFile $file): void
    {
        // Check file upload was successful
        if (! $file->isValid()) {
            throw new AuthorizationException(
                'invalid_file_upload',
                ['error' => $file->getErrorMessage()]
            );
        }

        // Validate MIME type
        $mimeType = $file->getMimeType();
        if ($mimeType === null || ! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new AuthorizationException(
                'invalid_file_type',
                [
                    'type' => $mimeType ?? 'unknown',
                    'allowed' => implode(', ', self::ALLOWED_MIME_TYPES),
                ]
            );
        }
    }

    /**
     * Get the uploaded file instance.
     *
     * @return UploadedFile The file instance
     */
    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    /**
     * Get the validated file name.
     *
     * @return FileName The file name value object
     */
    public function getFileName(): FileName
    {
        return $this->fileName;
    }

    /**
     * Get the validated file size.
     *
     * @return FileSize The file size value object
     */
    public function getFileSize(): FileSize
    {
        return $this->fileSize;
    }

    /**
     * Get the file MIME type.
     *
     * @return string The MIME type
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get the file extension.
     *
     * @return string The file extension (defaults to 'bin' if empty)
     */
    public function getExtension(): string
    {
        $extension = $this->file->extension();

        // extension() returns string, not nullable. Use empty check instead
        return $extension !== '' ? $extension : 'bin';
    }
}
