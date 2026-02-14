<?php

namespace Modules\Users\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

class UserCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public UserModel $user) {}
}
