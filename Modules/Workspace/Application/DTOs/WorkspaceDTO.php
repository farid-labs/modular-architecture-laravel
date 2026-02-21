<?php

namespace Modules\Workspace\Application\DTOs;

use Illuminate\Support\Str;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * Data Transfer Object for Workspace operations.
 *
 * Encapsulates workspace data for transfer between application layers.
 * Supports partial updates by allowing null values for optional fields.
 * Automatically generates URL-friendly slugs from workspace names.
 */
class WorkspaceDTO extends DataTransferObject
{
    /**
     * Workspace name.
     * Human-readable identifier for the workspace.
     * Used for display purposes and slug generation.
     */
    public ?string $name = null;

    /**
     * URL-friendly workspace slug.
     * Unique identifier for workspace URLs.
     * Generated from name if not explicitly provided.
     */
    public ?string $slug = null;

    /**
     * Optional workspace description.
     * Can contain detailed information about workspace purpose.
     */
    public ?string $description = null;

    /**
     * Owner user ID.
     * Tracks who created and owns the workspace.
     * Set automatically during workspace creation.
     */
    public ?int $owner_id = null;

    /**
     * Workspace status.
     * Defaults to 'active' if not specified.
     * Valid values: active, inactive, suspended
     */
    public string $status = 'active';

    /**
     * Create a WorkspaceDTO instance from an array.
     *
     * Filters out null values to support partial updates.
     * Allows flexible data input for workspace operations.
     *
     * @param  array<string, mixed>  $data  Associative array containing workspace data
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
     * Generates slug from name if not explicitly provided.
     * Uses Laravel's Str::slug for URL-friendly formatting.
     * Preserves all workspace properties for persistence.
     *
     * @return array<string, mixed> Associative array with workspace data
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
