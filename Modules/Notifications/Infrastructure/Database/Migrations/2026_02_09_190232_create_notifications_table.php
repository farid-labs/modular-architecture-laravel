<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop table if exists (for clean migration)
        Schema::dropIfExists('notifications');

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
            $table->string('locale')->default('en'); // User's preferred locale
            $table->json('channels')->nullable(); // Sent channels: ["database", "email", "sms", "push"]

            // Timestamps
            $table->timestamps();

            // Indexes for performance (with unique names to avoid conflicts)
            $table->index(['notifiable_type', 'notifiable_id'], 'notif_notifiable_index');
            $table->index('read_at', 'notif_read_at_index');
            $table->index('priority', 'notif_priority_index');
            $table->index('category', 'notif_category_index');
            $table->index('created_at', 'notif_created_at_index');
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notif_unread_count_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
