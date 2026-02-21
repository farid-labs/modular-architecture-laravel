<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * Data Transfer Object for Project operations.
 *
 * Encapsulates project data for transfer between application layers.
 * Supports both snake_case and camelCase array keys for flexibility.
 * Validates workspace ID and project status during instantiation.
 */
class ProjectDTO extends DataTransferObject
{
    /**
     * The project name.
     * Required field, must be between 3-100 characters.
     */
    public string $name;

    /**
     * Optional project description.
     * Can contain detailed information about project goals and scope.
     */
    public ?string $description = null;

    /**
     * Related workspace ID.
     * Associates the project with a specific workspace.
     * Must be a positive integer.
     */
    public int $workspaceId;

    /**
     * Current project status.
     * Defaults to ACTIVE if not specified.
     * Valid values: active, completed, archived
     */
    public ProjectStatus $status = ProjectStatus::ACTIVE;

    /**
     * Project creation timestamp.
     * Automatically set when project is created.
     */
    public ?CarbonInterface $createdAt = null;

    /**
     * Project last update timestamp.
     * Updated whenever project data is modified.
     */
    public ?CarbonInterface $updatedAt = null;

    /**
     * Create a ProjectDTO instance from an array.
     *
     * Accepts both snake_case and camelCase keys for workspace ID.
     * Validates workspace ID is provided and is a positive integer.
     * Maps status string to ProjectStatus enum if provided.
     *
     * @param  array<string, mixed>  $data  Associative array containing project data
     *
     * @throws \InvalidArgumentException If workspace ID is missing or invalid
     */
    public static function fromArray(array $data): self
    {
        // Accept both snake_case and camelCase keys for workspace ID
        $workspaceId = $data['workspace_id'] ?? $data['workspaceId'] ?? null;

        // Ensure workspace ID is provided
        if ($workspaceId === null || $workspaceId === '') {
            throw new \InvalidArgumentException(
                __('workspaces.workspace_id_required')
            );
        }

        // Validate workspace ID is a positive numeric value
        if (! is_numeric($workspaceId) || (int) $workspaceId <= 0) {
            throw new \InvalidArgumentException(
                __('workspaces.workspace_id_invalid', ['value' => $workspaceId])
            );
        }

        // Create and return the DTO instance
        return new self([
            // Require project name
            'name' => $data['name'] ?? throw new \InvalidArgumentException(__('workspaces.name_required')),

            // Optional description
            'description' => $data['description'] ?? null,

            // Cast workspace ID to integer
            'workspaceId' => (int) $workspaceId,

            // Map status if provided, otherwise default to ACTIVE
            'status' => isset($data['status'])
                ? ProjectStatus::from($data['status'])
                : ProjectStatus::ACTIVE,

            // Parse creation timestamp if provided
            'createdAt' => isset($data['created_at'])
                ? \Carbon\Carbon::parse($data['created_at'])
                : null,

            // Parse update timestamp if provided
            'updatedAt' => isset($data['updated_at'])
                ? \Carbon\Carbon::parse($data['updated_at'])
                : null,
        ]);
    }

    /**
     * Convert the DTO to an array representation.
     *
     * Transforms camelCase property names to snake_case for database persistence.
     * Converts enum values to their string representations.
     * Formats timestamps as ISO 8601 strings.
     *
     * @return array<string, mixed> Associative array with snake_case keys
     */
    public function toArray(): array
    {
        return [
            // Project name
            'name' => $this->name,

            // Project description
            'description' => $this->description,

            // Workspace ID in snake_case format
            'workspace_id' => $this->workspaceId,

            // Enum value of the project status
            'status' => $this->status->value,

            // ISO 8601 formatted creation timestamp
            'created_at' => $this->createdAt?->toIso8601String(),

            // ISO 8601 formatted update timestamp
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
