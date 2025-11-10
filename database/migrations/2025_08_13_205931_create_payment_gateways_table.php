<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Razorpay Gateways
        |--------------------------------------------------------------------------
        */
        Schema::create('razorpay_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();          // e.g. razorpay_main
            $table->string('display_name', 128);           // e.g. Razorpay (Primary)
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();

            // Required Razorpay keys
            $table->string('key_id', 191);
            $table->string('key_secret', 191);
            $table->string('webhook_secret', 191);

            // Optional vendor/extra config
            $table->json('credentials')->nullable();       // for any future flags

            $table->timestamps();
            $table->softDeletes();
        });

        /*
        |--------------------------------------------------------------------------
        | UPI Gateways
        |--------------------------------------------------------------------------
        */
        Schema::create('upi_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();          // e.g. upi_primary
            $table->string('display_name', 128);           // e.g. UPI – Corporate
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();

            // Required UPI fields
            $table->string('vpa', 191);                    // merchant@bank
            $table->string('merchant_name', 191)->nullable();
            $table->string('qr_code_path', 255)->nullable();   // stored image path if any
            $table->string('deeplink_base', 500)->nullable();  // e.g. upi://pay?pa={vpa}&pn={name}&am={amount}&cu=INR&tn={note}

            // Optional: webhook/vendor config from your PSP if you use one
            $table->json('credentials')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        /*
        |--------------------------------------------------------------------------
        | Cash Gateways
        |--------------------------------------------------------------------------
        */
        Schema::create('cash_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();          // e.g. cash_counter
            $table->string('display_name', 128)->default('Cash');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();

            // No credentials for cash
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('razorpay_gateways');
        Schema::dropIfExists('upi_gateways');
        Schema::dropIfExists('cash_gateways');
    }
};
