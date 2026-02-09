<?php

namespace Modules\Users\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Users\Domain\Entities\User;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        // In production, integrate with Mailgun, SendGrid, etc.
        \Log::info("Sending welcome email to {$this->user->email}");
        
        // Example: Mail::to($this->user->email)->send(new WelcomeEmail($this->user));
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error("Failed to send welcome email to {$this->user->email}: " . $exception->getMessage());
    }
}