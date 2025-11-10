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
        Schema::create('mailer_settings_admin', function (Blueprint $table) {
            $table->id();

            // mail driver (smtp, mailgun, etc.)
            $table->string('mailer')->default('smtp');
            // SMTP host
            $table->string('host');
            // SMTP port
            $table->integer('port');
            // SMTP username
            $table->string('username');
            // SMTP password
            $table->string('password');
            // encryption (tls, ssl, etc.)
            $table->string('encryption')->nullable();
            // From address and name
            $table->string('from_address');
            $table->string('from_name');

            // active / inactive flag
            $table->enum('status', ['active', 'inactive'])
                  ->default('active')
                  ->comment('Mailer setting status: active or inactive');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailer_settings_admin');
    }
};
