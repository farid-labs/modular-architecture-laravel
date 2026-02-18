<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Modules\Workspace\Domain\Entities\TaskCommentEntity;
use Modules\Workspace\Tests\TestCase;

class TaskCommentEntityTest extends TestCase
{
    public function test_task_comment_entity_is_immutable(): void
    {
        $comment = new TaskCommentEntity(1, 10, 5, 'Great work!');

        $this->assertEquals(1, $comment->getId());
        $this->assertEquals(10, $comment->getTaskId());
        $this->assertEquals(5, $comment->getUserId());
        $this->assertEquals('Great work!', $comment->getComment());
    }

    public function test_update_comment_returns_new_instance(): void
    {
        $original = new TaskCommentEntity(1, 10, 5, 'Old comment');
        $updated = $original->updateComment('New comment');

        $this->assertEquals('Old comment', $original->getComment());
        $this->assertEquals('New comment', $updated->getComment());
        $this->assertNotSame($original, $updated);
    }
}
