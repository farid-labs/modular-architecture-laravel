<?php

namespace Modules\Users\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Users\Domain\Entities\User;

class SendPasswordResetEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token
    ) {}

    public function handle(): void
    {
        \Log::info("Sending password reset email to {$this->user->email}");
        
        // Example: Mail::to($this->user->email)->send(new PasswordResetEmail($this->user, $this->token));
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error("Failed to send password reset email to {$this->user->email}: " . $exception->getMessage());
    }
}