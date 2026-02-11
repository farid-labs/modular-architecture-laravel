<?php

namespace Modules\Users\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Users\Infrastructure\Persistence\Models\User;

class UserUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user) {}
}
