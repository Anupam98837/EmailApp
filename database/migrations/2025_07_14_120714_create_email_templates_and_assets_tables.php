<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Templates table
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('template_uuid')->unique();
            // Associate each template with a user
            $table->foreignId('user_id')
                  ->constrained()            // references users.id
                  ->cascadeOnDelete();      // delete templates if user is removed

            $table->string('name', 255);
            $table->string('subject', 255);

            // rendered HTML
            $table->longText('body_html')->nullable();

            // design JSON for re-editing
            $table->json('body_design')
                  ->nullable()
                  ->comment('Unlayer design JSON for re-editing');

            // raw CE‑Builder HTML for round‑trip editing
            $table->longText('editable_html')
                  ->nullable()
                  ->comment('Raw CE‑Builder HTML for re‑editing');

            $table->boolean('is_active')
                  ->default(true)
                  ->comment('true = active, false = inactive');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
