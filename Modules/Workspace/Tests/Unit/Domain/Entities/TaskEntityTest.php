<?php

namespace Modules\Workspace\Tests\Unit\Domain\Entities;

use Carbon\Carbon;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;
use Modules\Workspace\Domain\ValueObjects\TaskTitle;
use Modules\Workspace\Tests\TestCase;

class TaskEntityTest extends TestCase
{
    // Test that a TaskEntity can be created and all properties are correctly set
    public function test_task_entity_can_be_created(): void
    {
        $task = new TaskEntity(
            1,
            new TaskTitle('Implement login feature'),
            'Create authentication flow',
            1,
            2,
            TaskStatus::PENDING,
            TaskPriority::HIGH,
            Carbon::tomorrow(),
            Carbon::now(),
            Carbon::now()
        );

        $this->assertEquals(1, $task->getId());
        $this->assertEquals('Implement login feature', $task->getTitleVO());
        $this->assertEquals(TaskStatus::PENDING, $task->getStatus());
        $this->assertEquals(TaskPriority::HIGH, $task->getPriority());
        $this->assertNotNull($task->getCreatedAt());
        $this->assertNotNull($task->getUpdatedAt());
    }

    // Test that marking a task as completed returns a new instance with updated status
    public function test_task_can_be_marked_as_completed(): void
    {
        $task = new TaskEntity(
            1,
            new TaskTitle('Test task'),
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::MEDIUM,
            null,
            Carbon::now(),
            Carbon::now()
        );

        $completedTask = $task->markAsCompleted();

        $this->assertTrue($completedTask->isCompleted());
        $this->assertEquals(TaskStatus::COMPLETED, $completedTask->getStatus());
        $this->assertNotSame($task, $completedTask);
    }

    // Test that a task is considered overdue when the due date has passed and it is not completed
    public function test_task_is_overdue_when_due_date_passed_and_not_completed(): void
    {
        $task = new TaskEntity(
            1,
            new TaskTitle('Overdue task'),
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::MEDIUM,
            Carbon::yesterday()
        );

        $this->assertTrue($task->isOverdue());
    }

    // Test that a completed task is never considered overdue
    public function test_task_is_not_overdue_when_completed(): void
    {
        $task = new TaskEntity(
            1,
            new TaskTitle('Completed task'),
            null,
            1,
            null,
            TaskStatus::COMPLETED,
            TaskPriority::MEDIUM,
            Carbon::yesterday()
        );

        $this->assertFalse($task->isOverdue());
    }

    // Test that a task with a future due date is not overdue
    public function test_task_is_not_overdue_when_due_date_in_future(): void
    {
        $task = new TaskEntity(
            1,
            new TaskTitle('Future task'),
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::MEDIUM,
            Carbon::tomorrow()
        );

        $this->assertFalse($task->isOverdue());
    }

    // Test that the TaskEntity can be converted to an array representation correctly
    public function test_task_can_be_converted_to_array(): void
    {
        $task = new TaskEntity(
            1,
            new TaskTitle('Test task'),
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

    // Test the behavior when optional fields are null
    public function test_task_with_null_fields(): void
    {
        $task = new TaskEntity(
            1,
            new TaskTitle('Simple task'),
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
