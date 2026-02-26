<?php

namespace Modules\Users\Tests\Unit\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Modules\Users\Application\DTOs\UserDTO;
use Modules\Users\Application\Services\CachedUserService;
use Modules\Users\Application\Services\UserService;
use Modules\Users\Domain\Entities\UserEntity;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Domain\ValueObjects\Name;
use Modules\Users\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Class CachedUserServiceTest
 *
 * Unit tests for CachedUserService.
 * Verifies caching behavior: first call hits service, subsequent calls hit cache.
 */
#[CoversClass(CachedUserServiceTest::class)]
class CachedUserServiceTest extends TestCase
{
    /** @var UserService|Mockery\MockInterface */
    protected UserService $userServiceMock;

    protected CachedUserService $cachedService;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Mock for underlying UserService
        $this->userServiceMock = Mockery::mock(UserService::class);

        // 2. Instantiate the service under test with the mock
        $this->cachedService = new CachedUserService($this->userServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush(); // Clean cache after each test to prevent leakage
        parent::tearDown();
    }

    /**
     * Helper method to create a fake UserEntity for testing.
     * Fixes: "Undefined method 'createMockEntity'"
     */
    protected function createMockEntity(int $id = 1): UserEntity
    {
        $now = CarbonImmutable::now();

        return new UserEntity(
            $id,
            new Name('Test User'),
            new Email('test@example.com'),
            $now,
            $now,
            $now,
            false
        );
    }

    #[Test]
    public function get_user_by_id_uses_cache_on_second_call(): void
    {
        // Arrange
        Cache::flush();
        $fakeEntity = $this->createMockEntity();
        $cacheKey = 'user_1';

        // Expectation: getUserById on the underlying service should be called EXACTLY once
        $this->userServiceMock
            ->shouldReceive('getUserById')
            ->once()
            ->with(1)
            ->andReturn($fakeEntity);

        // Act & Assert - First Call: Should hit the underlying service (DB)
        $result1 = $this->cachedService->getUserById(1);
        $this->assertEquals($fakeEntity->getId(), $result1->getId());
        $this->assertInstanceOf(UserEntity::class, $result1);

        // Act & Assert - Second Call: Should hit Cache (Service method should NOT be called again)
        $result2 = $this->cachedService->getUserById(1);
        $this->assertEquals($fakeEntity->getId(), $result2->getId());

        // Optional: Verify cache actually has the key (requires cache driver that supports has())
        // $this->assertTrue(Cache::has($cacheKey));
    }

    #[Test]
    public function update_user_invalidates_cache(): void
    {
        // Arrange
        Cache::flush();
        $fakeDto = new UserDTO(['name' => 'Updated', 'email' => 'new@example.com']);
        $fakeEntity = $this->createMockEntity();

        // Mock the underlying service call for update
        $this->userServiceMock
            ->shouldReceive('updateUser')
            ->once()
            ->with(1, $fakeDto)
            ->andReturn($fakeEntity);

        // Pre-populate cache to test invalidation
        Cache::put('user_1', 'old_value', 3600);
        Cache::put('users_list', 'old_list', 1800);

        // Act
        $result = $this->cachedService->updateUser(1, $fakeDto);

        // Assert
        $this->assertEquals($fakeEntity->getId(), $result->getId());

        // Verify cache keys were forgotten
        $this->assertNull(Cache::get('user_1'));
        $this->assertNull(Cache::get('users_list'));
    }
}
