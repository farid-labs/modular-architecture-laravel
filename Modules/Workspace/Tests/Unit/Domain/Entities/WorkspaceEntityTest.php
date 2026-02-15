<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Modules\Workspace\Domain\Entities\WorkspaceEntity;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Domain\ValueObjects\WorkspaceName;
use Modules\Workspace\Tests\TestCase;

class WorkspaceEntityTest extends TestCase
{
    public function test_workspace_entity_can_be_created(): void
    {
        $entity = new WorkspaceEntity(
            1,
            new WorkspaceName('Test Workspace'),
            'test-workspace',
            'Description',
            WorkspaceStatus::ACTIVE,
            1
        );

        $this->assertEquals(1, $entity->getId());
        $this->assertEquals('Test Workspace', $entity->getName());
        $this->assertTrue($entity->isActive());
    }

    public function test_update_name_updates_slug(): void
    {
        $entity = new WorkspaceEntity(
            1,
            new WorkspaceName('Old Name'),
            'old-name',
            null,
            WorkspaceStatus::ACTIVE,
            1
        );

        $entity->updateName(new WorkspaceName('New Name'));
        $this->assertEquals('New Name', $entity->getName());
        $this->assertEquals('new-name', $entity->getSlug());
    }
}
