<?php

namespace Modules\Users\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EmailTest::class)]
class EmailTest extends TestCase
{
    #[Test]
    public function test_valid_email_can_be_created(): void
    {
        $email = new Email('test@example.com');
        $this->assertEquals('test@example.com', $email->getValue());
    }

    #[Test]
    public function test_invalid_email_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Email('invalid-email');
    }

    #[Test]
    public function test_email_can_be_converted_to_string(): void
    {
        $email = new Email('test@example.com');
        $this->assertEquals('test@example.com', (string) $email);
    }
}
