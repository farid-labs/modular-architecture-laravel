<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Carbon\Carbon;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;
use Modules\Workspace\Tests\TestCase;

class TaskEntityTest extends TestCase
{
    public function test_task_entity_can_be_created(): void
    {
        $task = new TaskEntity(
            1,
            'Implement login feature',
            'Create authentication flow',
            1,
            2,
            TaskStatus::PENDING,
            TaskPriority::HIGH,
            Carbon::tomorrow()
        );

        $this->assertEquals(1, $task->getId());
        $this->assertEquals('Implement login feature', $task->getTitle());
        $this->assertEquals(TaskStatus::PENDING, $task->getStatus());
        $this->assertEquals(TaskPriority::HIGH, $task->getPriority());

        $dueDate = $task->getDueDate();
        $this->assertNotNull($dueDate, 'Due date should not be null');
        $this->assertTrue($dueDate->isTomorrow(), 'Due date should be tomorrow');
    }

    public function test_task_can_be_marked_as_completed(): void
    {
        $task = new TaskEntity(
            1,
            'Test task',
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::MEDIUM,
            null
        );

        $completedTask = $task->markAsCompleted();

        $this->assertTrue($completedTask->isCompleted());
        $this->assertEquals(TaskStatus::COMPLETED, $completedTask->getStatus());
        $this->assertNotSame($task, $completedTask); // Immutable pattern
    }

    public function test_task_is_overdue_when_due_date_passed_and_not_completed(): void
    {
        $task = new TaskEntity(
            1,
            'Overdue task',
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::MEDIUM,
            Carbon::yesterday()
        );

        $this->assertTrue($task->isOverdue());
    }

    public function test_task_is_not_overdue_when_completed(): void
    {
        $task = new TaskEntity(
            1,
            'Completed task',
            null,
            1,
            null,
            TaskStatus::COMPLETED,
            TaskPriority::MEDIUM,
            Carbon::yesterday()
        );

        $this->assertFalse($task->isOverdue());
    }

    public function test_task_is_not_overdue_when_due_date_in_future(): void
    {
        $task = new TaskEntity(
            1,
            'Future task',
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::MEDIUM,
            Carbon::tomorrow()
        );

        $this->assertFalse($task->isOverdue());
    }

    public function test_task_can_be_converted_to_array(): void
    {
        $task = new TaskEntity(
            1,
            'Test task',
            'Description',
            1,
            2,
            TaskStatus::IN_PROGRESS,
            TaskPriority::URGENT,
            Carbon::parse('2026-12-31')
        );

        $array = $task->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals('Test task', $array['title']);
        $this->assertEquals('in_progress', $array['status']);
        $this->assertEquals('urgent', $array['priority']);
        $this->assertEquals('2026-12-31T00:00:00+00:00', $array['due_date']);
    }

    public function test_task_with_null_fields(): void
    {
        $task = new TaskEntity(
            1,
            'Simple task',
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::LOW,
            null
        );

        $this->assertNull($task->getDescription());
        $this->assertNull($task->getAssignedTo());
        $this->assertNull($task->getDueDate());
        $this->assertFalse($task->isAssigned());
    }
}
