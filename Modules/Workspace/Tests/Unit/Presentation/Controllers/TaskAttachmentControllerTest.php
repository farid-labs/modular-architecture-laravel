<?php

namespace Modules\Workspace\Tests\Unit\Presentation\Controllers;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Domain\Entities\TaskAttachmentEntity;
use Modules\Workspace\Domain\Exceptions\AuthorizationException;
use Modules\Workspace\Domain\ValueObjects\FileName;
use Modules\Workspace\Domain\ValueObjects\FilePath;
use Modules\Workspace\Domain\ValueObjects\FileSize;
use Modules\Workspace\Presentation\Controllers\TaskAttachmentController;
use Modules\Workspace\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Unit tests for TaskAttachmentController.
 *
 * Tests controller-layer responsibilities:
 * - Request validation delegation
 * - Authentication enforcement
 * - Service layer interaction
 * - Response formatting
 * - Error handling and status codes
 * - Cache management integration
 *
 * Uses Mockery for service layer mocking to isolate controller behavior.
 *
 * @covers \Modules\Workspace\Presentation\Controllers\TaskAttachmentController
 *
 * @author Farid Labs
 * @copyright 2026 Farid Labs
 */
#[CoversClass(TaskAttachmentControllerTest::class)]
class TaskAttachmentControllerTest extends TestCase
{
    use WithFaker;

    /**
     * Mocked workspace service instance.
     *
     * @var WorkspaceService&MockInterface
     */
    private WorkspaceService $mockService;

    private TaskAttachmentController $controller;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock service using Mockery
        $this->mockService = Mockery::mock(WorkspaceService::class);
        $this->controller = new TaskAttachmentController($this->mockService);

        // Create test user
        $this->user = new UserModel;
        $this->user->id = 1;
        $this->user->name = 'Test User';
        $this->user->email = 'test@example.com';
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_index_requires_authentication(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        $request = Request::create('/tasks/1/attachments', 'GET');
        // No user attached to request

        $this->controller->index($request, 1);
    }

    #[Test]
    public function test_index_returns_properly_formatted_response(): void
    {
        // Create mock attachment entities
        $attachments = [
            new TaskAttachmentEntity(
                1,
                10,
                1,
                'application/pdf',
                new FileSize(102400),
                now(),
                now(),
                new FileName('report.pdf'),
                new FilePath('task-attachments/report.pdf')
            ),
        ];

        // Configure service mock using Mockery syntax (with userId parameter)
        $this->mockService
            ->shouldReceive('getAttachmentsByTask')
            ->once()
            ->with(10, 1) // Pass userId for authorization (taskId, userId)
            ->andReturn($attachments);

        // Create authenticated request
        $request = Request::create('/tasks/10/attachments', 'GET');
        $request->setUserResolver(fn () => $this->user);

        // Execute controller action
        $response = $this->controller->index($request, 10);

        // Verify response structure
        $this->assertEquals(200, $response->getStatusCode());
        $jsonData = $response->getData(true);

        $this->assertArrayHasKey('data', $jsonData);
        $this->assertArrayHasKey('message', $jsonData);
        $this->assertCount(1, $jsonData['data']);
        $this->assertEquals('report.pdf', $jsonData['data'][0]['file_name']);
    }

    #[Test]
    public function test_index_returns_forbidden_for_non_members(): void
    {
        // Configure service to throw authorization exception
        $this->mockService
            ->shouldReceive('getAttachmentsByTask')
            ->once()
            ->with(10, 1) // Pass userId for authorization
            ->andThrow(new AuthorizationException('not_member_of_project'));

        // Create authenticated request
        $request = Request::create('/tasks/10/attachments', 'GET');
        $request->setUserResolver(fn () => $this->user);

        // Execute controller action
        $response = $this->controller->index($request, 10);

        // Verify forbidden response
        $this->assertEquals(403, $response->getStatusCode());
        $jsonData = $response->getData(true);
        $this->assertEquals(__('workspaces.not_member_of_project'), $jsonData['message']);
    }

    #[Test]
    public function test_index_returns_not_found_for_missing_task(): void
    {
        // Configure service to throw not found exception
        $this->mockService
            ->shouldReceive('getAttachmentsByTask')
            ->once()
            ->with(999, 1) // Pass userId for authorization
            ->andThrow(new AuthorizationException('task_not_found', ['id' => 999]));

        // Create authenticated request
        $request = Request::create('/tasks/999/attachments', 'GET');
        $request->setUserResolver(fn () => $this->user);

        // Execute controller action
        $response = $this->controller->index($request, 999);

        // Verify not found response
        $this->assertEquals(404, $response->getStatusCode());
        $jsonData = $response->getData(true);
        $this->assertStringContainsString('not found', strtolower($jsonData['message']));
    }
}
