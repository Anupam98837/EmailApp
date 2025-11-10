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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();

            // Plan details
            $table->string('title');
            $table->text('description')->nullable();

            // Pricing & billing
            $table->decimal('price', 10, 2)->default(0.00);
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');

            // Mailer settings: support multiple mailer IDs (stored as JSON array)
            $table->json('mailer_settings_admin_ids')
                  ->nullable()
                  ->comment('Array of mailer_settings_admin IDs; null or empty = none');

            // Permission to add new mailers under this plan
            $table->boolean('can_add_mailer')
                  ->default(false)
                  ->comment('If true, user with this plan can add/configure additional mailers');

            // Usage limits (null means unlimited)
            $table->unsignedInteger('template_limit')->nullable()
                  ->comment('Max number of templates user can create in billing cycle; null = unlimited');
            $table->unsignedInteger('send_limit')->nullable()
                  ->comment('Max sends per billing cycle; null = unlimited');
            $table->unsignedInteger('list_limit')->nullable()
                  ->comment('Max number of lists; null = unlimited');

            // Discounts
            $table->decimal('discount', 5, 2)->nullable()
                  ->comment('Percentage discount (e.g. 10.00 = 10%)');

            // Plan status
            $table->enum('status', ['active', 'inactive'])
                  ->default('active')
                  ->comment('Plan status');

            $table->timestamps();

            // Indexes for common queries
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
