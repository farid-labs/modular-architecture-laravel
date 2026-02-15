<?php

namespace Modules\Workspace\Tests\Unit\Application\Services;

use Carbon\Carbon;
use Mockery\MockInterface;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Modules\Workspace\Tests\TestCase;

class WorkspaceServiceTest extends TestCase
{
    protected WorkspaceRepositoryInterface&MockInterface $repository;

    protected WorkspaceService $service;

    protected UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = \Mockery::mock(WorkspaceRepositoryInterface::class);
        $this->service = new WorkspaceService($this->repository);

        $this->user = new UserModel;
        $this->user->id = 1;
        $this->user->name = 'Test User';
        $this->user->email = 'test@example.com';
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_create_task_with_valid_data(): void
    {
        $taskDTO = TaskDTO::fromArray([
            'title' => 'New Task',
            'description' => 'Task description',
            'project_id' => 1,
            'assigned_to' => 2,
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => Carbon::tomorrow()->toDateTimeString(),
        ]);

        $expectedTask = new TaskEntity(
            1,
            'New Task',
            'Task description',
            1,
            2,
            TaskStatus::PENDING,
            TaskPriority::HIGH,
            Carbon::tomorrow()
        );

        $this->repository
            ->shouldReceive('isUserMemberOfProject')
            ->once()
            ->with(1, 1)
            ->andReturnTrue();

        $this->repository
            ->shouldReceive('createTask')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($taskDTO) {
                return $arg->title === $taskDTO->title;
            }))
            ->andReturn($expectedTask);

        $result = $this->service->createTask($taskDTO, $this->user);

        $this->assertEquals($expectedTask, $result);
    }

    public function test_complete_task_successfully(): void
    {
        $taskId = 1;
        $existingTask = new TaskEntity(
            $taskId,
            'Existing Task',
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::MEDIUM,
            null
        );

        $completedTask = $existingTask->markAsCompleted();

        $this->repository
            ->shouldReceive('findTaskById')
            ->once()
            ->with($taskId)
            ->andReturn($existingTask);

        $this->repository
            ->shouldReceive('isUserMemberOfProject')
            ->once()
            ->with(1, 1)
            ->andReturnTrue();

        $this->repository
            ->shouldReceive('updateTask')
            ->once()
            ->with($taskId, \Mockery::on(function ($arg) use ($completedTask) {
                return $arg->status->value === $completedTask->getStatus()->value;
            }))
            ->andReturn($completedTask);

        $result = $this->service->completeTask($taskId, $this->user);

        $this->assertTrue($result->isCompleted());
        $this->assertEquals(TaskStatus::COMPLETED, $result->getStatus());
    }

    public function test_cannot_complete_task_if_not_member_of_project(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User does not have permission');

        $taskId = 1;
        $existingTask = new TaskEntity(
            $taskId,
            'Task',
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::MEDIUM,
            null
        );

        $this->repository
            ->shouldReceive('findTaskById')
            ->once()
            ->with($taskId)
            ->andReturn($existingTask);

        $this->repository
            ->shouldReceive('isUserMemberOfProject')
            ->once()
            ->with(1, 1)
            ->andReturnFalse();

        $this->service->completeTask($taskId, $this->user);
    }

    public function test_cannot_create_task_with_past_due_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Due date cannot be in the past');

        $taskDTO = TaskDTO::fromArray([
            'title' => 'Invalid Task',
            'project_id' => 1,
            'due_date' => Carbon::yesterday()->toDateTimeString(),
        ]);

        $this->repository
            ->shouldReceive('isUserMemberOfProject')
            ->once()
            ->with(1, 1)
            ->andReturnTrue();

        $this->service->createTask($taskDTO, $this->user);
    }
}
