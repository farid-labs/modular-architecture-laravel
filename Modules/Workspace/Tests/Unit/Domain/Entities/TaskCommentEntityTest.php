<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use Modules\Workspace\Tests\TestCase;

class TaskCommentEntityTest extends TestCase
{
    /**
     * Ensure that the entity is immutable in practice.
     * All getters return the expected values.
     */
    public function test_task_comment_entity_is_immutable(): void
    {
        $comment = new TaskCommentEntity(1, 10, 5, 'Great work!');

        $this->assertEquals(1, $comment->getId());
        $this->assertEquals(10, $comment->getTaskId());
        $this->assertEquals(5, $comment->getUserId());
        $this->assertEquals('Great work!', $comment->getComment());
    }

    /**
     * Updating the comment should return a new instance, not mutate the original.
     */
    public function test_update_comment_returns_new_instance(): void
    {
        $original = new TaskCommentEntity(1, 10, 5, 'Old comment');
        $updated = $original->updateComment('New comment');

        // Original remains unchanged
        $this->assertEquals('Old comment', $original->getComment());

        // Updated instance contains the new value
        $this->assertEquals('New comment', $updated->getComment());

        // Ensure a new object is returned
        $this->assertNotSame($original, $updated);
    }
}
