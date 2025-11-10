<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('plan_id')->index();
            $table->string('gateway'); // e.g., razorpay
            $table->string('gateway_payment_id')->nullable()->index(); // order id / payment id
            $table->decimal('amount_decimal', 12, 2);
            $table->string('currency', 10)->default('INR');
            $table->enum('billing_cycle', ['monthly', 'yearly']);
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->json('metadata')->nullable(); // raw webhook payload etc.
            $table->timestamps();

            $table->foreign('user_id')->cascadeOnDelete()->references('id')->on('users');
            $table->foreign('plan_id')->cascadeOnDelete()->references('id')->on('subscription_plans');
        });

        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('plan_id')->index();
            $table->enum('billing_cycle', ['monthly', 'yearly']);
            $table->decimal('amount_decimal', 12, 2);
            $table->string('currency', 10)->default('INR');
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->unsignedBigInteger('payment_id')->nullable()->index(); // links to subscription_payments
            $table->json('metadata')->nullable(); // any extra info
            $table->timestamps();

            $table->foreign('user_id')->cascadeOnDelete()->references('id')->on('users');
            $table->foreign('plan_id')->cascadeOnDelete()->references('id')->on('subscription_plans');
            $table->foreign('payment_id')->nullOnDelete()->references('id')->on('subscription_payments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('subscription_payments');
    }
};
