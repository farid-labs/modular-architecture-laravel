<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Modules\Workspace\Tests\TestCase;

class ProjectEntityTest extends TestCase
{
    /**
     * Test that a ProjectEntity can be properly instantiated
     * and that getters return the correct values.
     */
    public function test_project_entity_can_be_created(): void
    {
        $project = new ProjectEntity(
            1,                        // Project ID
            'Website Redesign',       // Name
            'Redesign company website', // Description
            10,                       // Workspace ID
            ProjectStatus::ACTIVE     // Status
        );

        $this->assertEquals(1, $project->getId());
        $this->assertEquals('Website Redesign', $project->getName());
        $this->assertEquals(10, $project->getWorkspaceId());

        // Check that status-related helper method works
        $this->assertTrue($project->isActive());
    }

    /**
     * Test the status helper methods (isActive, isCompleted, isArchived)
     * for each possible ProjectStatus enum value.
     */
    public function test_project_status_methods(): void
    {
        $active = new ProjectEntity(1, 'Test', null, 1, ProjectStatus::ACTIVE);
        $completed = new ProjectEntity(1, 'Test', null, 1, ProjectStatus::COMPLETED);
        $archived = new ProjectEntity(1, 'Test', null, 1, ProjectStatus::ARCHIVED);

        // Verify each helper method returns true for the correct status
        $this->assertTrue($active->isActive());
        $this->assertTrue($completed->isCompleted());
        $this->assertTrue($archived->isArchived());
    }
}
