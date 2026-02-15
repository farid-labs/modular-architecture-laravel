<?php

namespace Modules\Workspace\Domain\Entities;

use Modules\Workspace\Domain\Enums\ProjectStatus;

class ProjectEntity
{
    private int $id;

    private string $name;

    private ?string $description;

    private int $workspaceId;

    private ProjectStatus $status;

    public function __construct(
        int $id,
        string $name,
        ?string $description,
        int $workspaceId,
        ProjectStatus $status
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->workspaceId = $workspaceId;
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    // Additional domain methods as needed (e.g., completeProject)
}
