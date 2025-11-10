<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //
        // 1) campaigns
        //
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();

            // unique campaign identifier
            $table->uuid('campaign_uuid')->unique();

            // owner
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // which list to send to
            $table->foreignId('list_id')
                  ->constrained('lists')
                  ->cascadeOnDelete();

            // which template to use
            $table->foreignId('template_id')
                  ->constrained('templates')
                  ->cascadeOnDelete();

            // human-readable title
            $table->string('title', 255);

            // one-off subject override
            $table->string('subject_override', 255)
                  ->nullable()
                  ->comment('If set, use this instead of the template default subject');

            // attachments array (relative paths)
            $table->json('attachments')
                  ->nullable()
                  ->comment('JSON array of public/assets/campaign_attachments/... file paths');

            // dynamic From/Reply fields
            $table->string('reply_to_address', 255)
                  ->comment('Reply-To address for this campaign');
            $table->string('from_address', 255)
                  ->comment('From address for this campaign');
            $table->string('from_name', 255)
                  ->comment('From display name for this campaign');

            // UTM parameters
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_term', 100)->nullable();
            $table->string('utm_content', 100)->nullable();

            // when to send
            $table->dateTime('scheduled_at')
                  ->comment('Date & Time when campaign will be sent');

            // flags
            $table->boolean('is_active')
                  ->default(true)
                  ->comment('true = active, false = paused/deleted');
            $table->boolean('has_run')
                  ->default(false)
                  ->comment('set to true once the campaign has been executed');

            // delivery counters
            $table->unsignedInteger('sent_count')
                  ->default(0);
            $table->unsignedInteger('skipped_count')
                  ->default(0);
            $table->unsignedInteger('soft_bounce_count')
                  ->default(0);
            $table->unsignedInteger('hard_bounce_count')
                  ->default(0);
            $table->unsignedInteger('failed_count')
                  ->default(0);

            $table->timestamps();
        });

        //
        // 2) events (opens & clicks)
        //
        Schema::create('campaign_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('campaign_uuid')->index();
            $table->foreignId('subscriber_id')
                  ->constrained('list_users')
                  ->cascadeOnDelete();
            $table->enum('type', ['open', 'click', 'unsubscribe'])->index();
            $table->text('url')->nullable();
            $table->string('ip_address',45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        //
        // 3) bounces
        //
        Schema::create('campaign_bounces', function (Blueprint $table) {
            $table->id();
            $table->uuid('campaign_uuid')->index();
            $table->foreignId('subscriber_id')
                  ->constrained('list_users')
                  ->cascadeOnDelete();
            $table->enum('bounce_type', ['soft','hard'])->index();
            $table->string('reason')->nullable();
            $table->text('error_user_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        //
        // 4) skips
        //
        Schema::create('campaign_skips', function (Blueprint $table) {
            $table->id();
            $table->uuid('campaign_uuid')->index();
            $table->foreignId('subscriber_id')
                  ->constrained('list_users')
                  ->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->text('error_user_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        //
        // 5) failures
        //
        Schema::create('campaign_failures', function (Blueprint $table) {
            $table->id();
            $table->uuid('campaign_uuid')->index();
            $table->foreignId('subscriber_id')
                  ->constrained('list_users')
                  ->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->text('error_user_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        //
        // 6) deliveries
        //
        Schema::create('campaign_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('campaign_uuid')->index();
            $table->foreignId('subscriber_id')
                  ->constrained('list_users')
                  ->cascadeOnDelete();
            $table->enum('status', [
                'sent','skipped','soft_bounce','hard_bounce','failed'
            ])->index();
            $table->text('error_user_message')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_deliveries');
        Schema::dropIfExists('campaign_failures');
        Schema::dropIfExists('campaign_skips');
        Schema::dropIfExists('campaign_bounces');
        Schema::dropIfExists('campaign_events');
        Schema::dropIfExists('campaigns');
    }
};
