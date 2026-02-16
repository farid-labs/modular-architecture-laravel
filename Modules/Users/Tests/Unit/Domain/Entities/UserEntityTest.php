<?php

namespace Modules\Users\Tests\Unit\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Users\Domain\Entities\UserEntity;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Domain\ValueObjects\Name;
use Modules\Users\Tests\TestCase;

class UserEntityTest extends TestCase
{
    public function test_user_entity_can_be_created(): void
    {
        $now = CarbonImmutable::now();
        $entity = new UserEntity(
            1,
            new Name('John Doe'),
            new Email('john@example.com'),
            null, // emailVerifiedAt
            $now, // createdAt
            $now, // updatedAt
            false // isAdmin
        );

        $this->assertEquals(1, $entity->getId());
        $this->assertEquals('John Doe', $entity->getName()->getValue());
        $this->assertEquals('john@example.com', $entity->getEmail()->getValue());
        $this->assertFalse($entity->isActive()); // emailVerifiedAt = null
    }

    public function test_user_entity_is_active_when_email_verified(): void
    {
        $now = CarbonImmutable::now();
        $entity = new UserEntity(
            1,
            new Name('John Doe'),
            new Email('john@example.com'),
            $now,
            $now,
            $now,
            false
        );

        $this->assertTrue($entity->isActive());
    }

    public function test_user_entity_update_name(): void
    {
        $now = CarbonImmutable::now();
        $entity = new UserEntity(
            1,
            new Name('John Doe'),
            new Email('john@example.com'),
            null,
            $now,
            $now,
            false
        );

        $updatedEntity = $entity->updateName(new Name('Jane Doe'));
        $this->assertEquals('Jane Doe', $updatedEntity->getName()->getValue());
        $this->assertEquals('John Doe', $entity->getName()->getValue());
    }
}
