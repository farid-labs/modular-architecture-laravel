<?php

namespace Modules\Workspace\Tests\Unit\Application\Services;

use Carbon\Carbon;
use Mockery\MockInterface;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Application\DTOs\TaskDTO;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Domain\Entities\ProjectEntity;
use Modules\Workspace\Domain\Entities\TaskEntity;
use Modules\Workspace\Domain\Enums\ProjectStatus;
use Modules\Workspace\Domain\Enums\TaskPriority;
use Modules\Workspace\Domain\Enums\TaskStatus;
use Modules\Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Modules\Workspace\Domain\ValueObjects\TaskTitle;
use Modules\Workspace\Tests\TestCase;

class WorkspaceServiceTest extends TestCase
{
    protected WorkspaceRepositoryInterface&MockInterface $repository;

    protected WorkspaceService $service;

    protected UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the repository
        $this->repository = \Mockery::mock(WorkspaceRepositoryInterface::class);

        // Initialize the service with the mocked repository
        $this->service = new WorkspaceService($this->repository);

        // Setup a test user
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

    /**
     * Test creating a task with valid data
     */
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

        $expectedProject = new ProjectEntity(1, 'Test Project', null, 10, ProjectStatus::ACTIVE);
        $expectedTask = new TaskEntity(
            1,
            new TaskTitle('New Task'),
            'Task description',
            1,
            2,
            TaskStatus::PENDING,
            TaskPriority::HIGH,
            Carbon::tomorrow()
        );

        // Mock repository: project must exist
        $this->repository
            ->shouldReceive('findProjectById')
            ->once()
            ->with(1)
            ->andReturn($expectedProject);

        // Mock repository: user must be a member of the project
        $this->repository
            ->shouldReceive('isUserMemberOfProject')
            ->once()
            ->with(1, 1)
            ->andReturnTrue();

        // Mock repository: createTask should return expected TaskEntity
        $this->repository
            ->shouldReceive('createTask')
            ->once()
            ->with(\Mockery::on(fn($arg) => $arg->title === $taskDTO->title))
            ->andReturn($expectedTask);

        $result = $this->service->createTask($taskDTO, $this->user);

        $this->assertEquals($expectedTask, $result);
    }

    /**
     * Test completing a task successfully
     */
    public function test_complete_task_successfully(): void
    {
        $taskId = 1;
        $existingTask = new TaskEntity(
            $taskId,
            new TaskTitle('Existing Task'),
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::MEDIUM,
            null
        );

        $completedTask = $existingTask->markAsCompleted();

        // Mock repository: find task by ID
        $this->repository
            ->shouldReceive('findTaskById')
            ->once()
            ->with($taskId)
            ->andReturn($existingTask);

        // Mock repository: user must be a member
        $this->repository
            ->shouldReceive('isUserMemberOfProject')
            ->once()
            ->with(1, 1)
            ->andReturnTrue();

        // Mock repository: update task with new status
        $this->repository
            ->shouldReceive('updateTask')
            ->once()
            ->with($taskId, \Mockery::on(fn($arg) => $arg->status->value === $completedTask->getStatus()->value))
            ->andReturn($completedTask);

        $result = $this->service->completeTask($taskId, $this->user);

        $this->assertTrue($result->isCompleted());
        $this->assertEquals(TaskStatus::COMPLETED, $result->getStatus());
    }

    /**
     * Test that completing a task fails if user is not a member
     */
    public function test_cannot_complete_task_if_not_member_of_project(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(__('workspaces.not_member_of_workspace'));

        $taskId = 1;
        $existingTask = new TaskEntity(
            $taskId,
            new TaskTitle('Task title'),
            null,
            1,
            null,
            TaskStatus::PENDING,
            TaskPriority::MEDIUM,
            null
        );

        // Mock repository: find task
        $this->repository
            ->shouldReceive('findTaskById')
            ->once()
            ->with($taskId)
            ->andReturn($existingTask);

        // Mock repository: user is NOT a member
        $this->repository
            ->shouldReceive('isUserMemberOfProject')
            ->once()
            ->with(1, 1)
            ->andReturnFalse();

        $this->service->completeTask($taskId, $this->user);
    }

    /**
     * Test that creating a task fails if due date is in the past
     */
    public function test_cannot_create_task_with_past_due_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(__('workspaces.date_cannot_past'));

        $taskDTO = TaskDTO::fromArray([
            'title' => 'Invalid Task',
            'project_id' => 1,
            'due_date' => Carbon::yesterday()->toDateTimeString(),
        ]);

        $expectedProject = new ProjectEntity(1, 'Test Project', null, 10, ProjectStatus::ACTIVE);

        // Mock repository: project must exist
        $this->repository
            ->shouldReceive('findProjectById')
            ->once()
            ->with(1)
            ->andReturn($expectedProject);

        // Mock repository: user is a member
        $this->repository
            ->shouldReceive('isUserMemberOfProject')
            ->once()
            ->with(1, 1)
            ->andReturnTrue();

        $this->service->createTask($taskDTO, $this->user);
    }
}
