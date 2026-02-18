<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Modules\Workspace\Tests\TestCase;

class ProjectEntityTest extends TestCase
{
    public function test_project_entity_can_be_created(): void
    {
        $project = new ProjectEntity(
            1,
            'Website Redesign',
            'Redesign company website',
            10,
            ProjectStatus::ACTIVE
        );

        $this->assertEquals(1, $project->getId());
        $this->assertEquals('Website Redesign', $project->getName());
        $this->assertEquals(10, $project->getWorkspaceId());
        $this->assertTrue($project->isActive());
    }

    public function test_project_status_methods(): void
    {
        $active = new ProjectEntity(1, 'Test', null, 1, ProjectStatus::ACTIVE);
        $completed = new ProjectEntity(1, 'Test', null, 1, ProjectStatus::COMPLETED);
        $archived = new ProjectEntity(1, 'Test', null, 1, ProjectStatus::ARCHIVED);

        $this->assertTrue($active->isActive());
        $this->assertTrue($completed->isCompleted());
        $this->assertTrue($archived->isArchived());
    }
}
