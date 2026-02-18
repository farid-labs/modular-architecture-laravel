<?php

namespace Modules\Workspace\Application\DTOs;

use Carbon\CarbonInterface;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Spatie\DataTransferObject\DataTransferObject;

class ProjectDTO extends DataTransferObject
{
    public string $name;

    public ?string $description = null;

    public int $workspaceId;

    public ProjectStatus $status = ProjectStatus::ACTIVE;

    public ?CarbonInterface $createdAt = null;

    public ?CarbonInterface $updatedAt = null;

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'workspaceId' => $data['workspace_id'],
            'status' => isset($data['status'])
                ? ProjectStatus::from($data['status'])
                : ProjectStatus::ACTIVE,
            'createdAt' => isset($data['created_at'])
                ? \Carbon\Carbon::parse($data['created_at'])
                : null,
            'updatedAt' => isset($data['updated_at'])
                ? \Carbon\Carbon::parse($data['updated_at'])
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'workspace_id' => $this->workspaceId,
            'status' => $this->status->value,
        ];
    }
}
