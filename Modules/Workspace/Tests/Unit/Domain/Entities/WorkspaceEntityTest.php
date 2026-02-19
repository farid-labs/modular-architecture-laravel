<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Carbon\Carbon;
use Modules\Workspace\Domain\Entities\WorkspaceEntity;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Tests\TestCase;

class WorkspaceEntityTest extends TestCase
{
    // Test that a WorkspaceEntity can be created and its properties are correctly set
    public function test_workspace_entity_can_be_created(): void
    {
        $now = Carbon::now();

        $entity = new WorkspaceEntity(
            1,
            'Test Workspace',
            'test-workspace',
            'Description',
            WorkspaceStatus::ACTIVE,
            1,
            $now,
            $now,
            1,  // members_count
            0   // projects_count
        );

        $this->assertEquals(1, $entity->getId());
        $this->assertEquals('Test Workspace', $entity->getName());
        $this->assertEquals(1, $entity->getMembersCount());
        $this->assertTrue($entity->isActive());
    }

    // Test that the withName method returns a new instance with updated name and slug, without modifying the original
    public function test_with_name_returns_new_instance_with_updated_values(): void
    {
        $now = Carbon::now();
        $original = new WorkspaceEntity(
            1,
            'Old Name',
            'old-name',
            'Description',
            WorkspaceStatus::ACTIVE,
            1,
            $now,
            $now,
            0,
            0
        );

        $updated = $original->withName('New Name');

        // Original instance remains unchanged
        $this->assertEquals('Old Name', $original->getName());
        $this->assertEquals('old-name', $original->getSlug());

        // Updated instance has new values and updated timestamp
        $this->assertEquals('New Name', $updated->getName());
        $this->assertEquals('new-name', $updated->getSlug());
        $this->assertTrue($updated->getUpdatedAt()->greaterThan($original->getUpdatedAt()));
    }
}
