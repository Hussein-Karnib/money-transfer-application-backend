<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable'); // user_id, agent_id, admin_id, etc.
            
            // Notification type preferences
            $table->boolean('transfer_initiated')->default(true);
            $table->boolean('transfer_status_update')->default(true);
            $table->boolean('beneficiary_action')->default(true);
            $table->boolean('verification_required')->default(true);
            $table->boolean('promotion_alert')->default(true);
            $table->boolean('security_alert')->default(true);
            $table->boolean('commission_earned')->default(true);
            $table->boolean('settlement_alert')->default(true);
            $table->boolean('admin_message')->default(true);
            $table->boolean('system_alert')->default(true);
            
            // Channel preferences
            $table->boolean('channel_email')->default(true);
            $table->boolean('channel_sms')->default(false); // SMS is opt-in
            $table->boolean('channel_push')->default(true);
            $table->boolean('channel_in_app')->default(true);
            $table->boolean('channel_dashboard')->default(true);
            
            // Digest preferences
            $table->enum('email_frequency', ['instant', 'daily', 'weekly', 'never'])->default('daily');
            $table->enum('sms_frequency', ['instant', 'daily', 'weekly', 'never'])->default('never');
            $table->time('digest_send_time')->default('09:00'); // Time to send daily digest
            
            // Mute settings
            $table->boolean('mute_all')->default(false);
            $table->timestamp('mute_until')->nullable();
            
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->nullable();
            $table->morphs('notifiable');
            
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->text('description')->nullable();
            
            // Channels sent to
            $table->json('channels'); // ['email', 'sms', 'push', 'in_app', 'dashboard']
            
            // Metadata
            $table->morphs('related'); // polymorphic relation to Transfer, Agent, etc.
            $table->json('data')->nullable();
            
            // Tracking
            $table->timestamp('sent_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('opened_at')->nullable(); // For email/push opens
            $table->json('delivery_status')->nullable(); // Track each channel's delivery
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_preferences');
    }
};
