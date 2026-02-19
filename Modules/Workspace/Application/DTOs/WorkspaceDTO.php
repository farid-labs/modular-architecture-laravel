<?php

namespace Modules\Workspace\Application\DTOs;

use Illuminate\Support\Str;
use Spatie\DataTransferObject\DataTransferObject;

class WorkspaceDTO extends DataTransferObject
{
    // Workspace name
    public ?string $name = null;

    // URL-friendly workspace slug
    public ?string $slug = null;

    // Optional workspace description
    public ?string $description = null;

    // Owner user ID
    public ?int $owner_id = null;

    // Workspace status (default: active)
    public string $status = 'active';

    /**
     * Create a WorkspaceDTO instance from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Remove null values before creating the DTO
        $filteredData = array_filter($data, fn ($value) => $value !== null);

        return new self($filteredData);
    }

    /**
     * Convert the DTO to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            // Workspace name
            'name' => $this->name,

            // Generate slug from provided slug or fallback to name
            'slug' => $this->slug
                ? Str::slug($this->slug)
                : ($this->name ? Str::slug($this->name) : null),

            // Workspace description
            'description' => $this->description,

            // Owner ID
            'owner_id' => $this->owner_id,

            // Workspace status
            'status' => $this->status,
        ];
    }
}
