<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // Notification type (info, success, warning, error)
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('category')->default('system'); // system, user, workspace, project, task, security
            $table->morphs('notifiable'); // Polymorphic relation (user, workspace, etc.)

            // Notification content stored as JSON for flexibility
            $table->json('data'); // Contains title, message, action_url, metadata

            // Status tracking
            $table->timestamp('read_at')->nullable();
            $table->timestamp('deleted_at')->nullable(); // Soft delete for notifications

            // Metadata
            $table->string('locale')->default('fa'); // User's preferred locale
            $table->json('channels')->nullable(); // Sent channels: ["database", "email", "sms", "push"]

            // Timestamps
            $table->timestamps();

            // Indexes for performance
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('read_at');
            $table->index('priority');
            $table->index('category');
            $table->index('created_at');
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']); // For unread count
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
