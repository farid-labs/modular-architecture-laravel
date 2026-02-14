<?php

namespace Modules\Users\Tests\Unit\Domain\Entities;

use Modules\Users\Domain\Entities\UserEntity;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Domain\ValueObjects\Name;
use Modules\Users\Tests\TestCase;

class UserEntityTest extends TestCase
{
    public function test_user_entity_can_be_created(): void
    {
        $entity = new UserEntity(
            1,
            new Name('John Doe'),
            new Email('john@example.com')
        );

        $this->assertEquals(1, $entity->getId());
        $this->assertEquals('John Doe', $entity->getFullName());
        $this->assertEquals('john@example.com', $entity->getEmail()->getValue());
    }

    public function test_user_entity_is_active_when_email_verified(): void
    {
        $entity = new UserEntity(
            1,
            new Name('John Doe'),
            new Email('john@example.com'),
            null,
            false,
            \Carbon\CarbonImmutable::now()
        );

        $this->assertTrue($entity->isActive());
    }

    public function test_user_entity_update_name(): void
    {
        $entity = new UserEntity(
            1,
            new Name('John Doe'),
            new Email('john@example.com')
        );

        $entity->updateName(new Name('Jane Doe'));
        $this->assertEquals('Jane Doe', $entity->getFullName());
    }
}
