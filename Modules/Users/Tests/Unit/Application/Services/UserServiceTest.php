<?php

namespace Modules\Users\Tests\Unit\Application\Services;

use Tests\TestCase;
use Mockery\MockInterface;
use Modules\Users\Application\Services\UserService;
use Modules\Users\Domain\Repositories\UserRepositoryInterface;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Infrastructure\Persistence\Models\User;
use Modules\Users\Domain\Exceptions\UserNotFoundException;

class UserServiceTest extends TestCase
{

    protected UserRepositoryInterface&MockInterface $userRepository;

    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = \Mockery::mock(UserRepositoryInterface::class);
        $this->userService = new UserService($this->userRepository);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_create_user_calls_repository(): void
    {
        $userDTO = UserDTO::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ]);

        $user = new User();
        $user->id = 1;
        $user->name = 'John Doe';
        $user->email = 'john@example.com';

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('john@example.com')
            ->andReturnNull();

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->with($userDTO)
            ->andReturn($user);

        $result = $this->userService->createUser($userDTO);

        $this->assertSame($user, $result);
    }

    public function test_get_user_by_id_returns_user(): void
    {
        $user = new User();
        $user->id = 1;
        $user->name = 'John Doe';

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($user);

        $result = $this->userService->getUserById(1);

        $this->assertEquals($user, $result);
    }

    public function test_get_user_by_id_throws_exception_when_not_found(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturnNull();

        $this->userService->getUserById(1);
    }
}
