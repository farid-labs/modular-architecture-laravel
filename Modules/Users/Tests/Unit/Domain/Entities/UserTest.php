<?php

namespace Modules\Users\Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Domain\ValueObjects\Name;

class UserTest extends TestCase
{
    public function test_user_can_be_created(): void
    {
        $user = new User();
        $user->name = 'John Doe';
        $user->email = 'john@example.com';

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    public function test_user_is_active_when_email_verified(): void
    {
        $user = new User();
        $user->email_verified_at = now();

        $this->assertTrue($user->isActive());
    }

    public function test_user_is_not_active_when_email_not_verified(): void
    {
        $user = new User();
        $user->email_verified_at = null;

        $this->assertFalse($user->isActive());
    }

    public function test_user_can_get_full_name(): void
    {
        $user = new User();
        $user->name = 'John Doe';

        $this->assertEquals('John Doe', $user->getFullName());
    }
}