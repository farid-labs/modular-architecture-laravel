<?php

namespace Modules\Users\Tests\Unit\Infrastructure\Models\UserTest;

use Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Modules\Users\Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_user_model_can_be_created(): void
    {
        $model = new UserModel;
        $model->name = 'John Doe';
        $model->email = 'john@example.com';

        $this->assertEquals('John Doe', $model->name);
        $this->assertEquals('john@example.com', $model->email);
    }

    public function test_user_model_is_admin_attribute(): void
    {
        $model = new UserModel;
        $model->is_admin = true;

        $this->assertTrue($model->getIsAdminAttribute());
    }
}
