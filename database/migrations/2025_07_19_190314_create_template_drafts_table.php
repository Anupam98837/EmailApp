<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_drafts', function (Blueprint $table) {
            $table->id();

            // Optional link to a finalized template (nullable until published)
            $table->foreignId('template_id')
                  ->nullable()
                  ->constrained('templates')
                  ->nullOnDelete();

            // Author / owner
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Stable draft UUID for external references
            $table->uuid('draft_uuid')->unique();

            // Human label / working name (can differ from final template name)
            $table->string('name', 255)->nullable();

            // Subject line draft
            $table->string('subject', 255)->nullable();

            // Current HTML snapshot (rendered from design, optional)
            $table->longText('body_html')->nullable();

            // Raw Unlayer design JSON (editable source)
            $table->json('body_design')->nullable();

            // **Raw editable HTML** (CE‑Builder draft source)
            $table->longText('editable_html')->nullable();

            // Version number per (template_id OR user) scope.
            // If linked to a template, version increments per template.
            $table->unsignedInteger('version')->default(1);

            // Draft status (enum-like) – use check or plain string
            $table->string('status', 32)
                  ->default('draft')
                  ->comment('draft|review|approved|archived');

            // Whether this draft is the "current" working draft for its template
            $table->boolean('is_current')->default(true);

            // Optional change summary / notes
            $table->text('changelog')->nullable();

            $table->timestamps();

            // Useful composite indexes
            $table->index(['template_id', 'is_current']);
            $table->index(['template_id', 'version']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_drafts');
    }
};
