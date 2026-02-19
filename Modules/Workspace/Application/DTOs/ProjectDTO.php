<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Spatie\DataTransferObject\DataTransferObject;

class ProjectDTO extends DataTransferObject
{
    // The project name
    public string $name;

    // Optional project description
    public ?string $description = null;

    // Related workspace ID
    public int $workspaceId;

    // Current project status (default: ACTIVE)
    public ProjectStatus $status = ProjectStatus::ACTIVE;

    // Project creation timestamp
    public ?CarbonInterface $createdAt = null;

    // Project last update timestamp
    public ?CarbonInterface $updatedAt = null;

    /**
     * Create a ProjectDTO instance from an array.
     *
     * @param  array<string, mixed>  $data
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
     * @return array<string, mixed>
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
