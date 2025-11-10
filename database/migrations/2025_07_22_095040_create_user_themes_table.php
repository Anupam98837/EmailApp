<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Master themes users can CRUD (single logo column)
        Schema::create('themes', function (Blueprint $table) {
            $table->id();

            // Required: unique name for the theme
            $table->string('name', 255)->unique();

            // Branding (ONE logo column only)
            $table->string('app_name', 255)->nullable();
            $table->string('logo_url', 2048)->nullable();

            // Palette
            $table->string('primary_color',   20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('accent_color',    20)->nullable();
            $table->string('light_color',     20)->nullable();
            $table->string('border_color',    20)->nullable();
            $table->string('text_color',      20)->nullable();
            $table->string('bg_body',         20)->nullable();

            // Semantic colors
            $table->string('info_color',     20)->nullable();
            $table->string('success_color',  20)->nullable();
            $table->string('warning_color',  20)->nullable();
            $table->string('danger_color',   20)->nullable();

            // Fonts
            $table->string('font_sans', 255)->nullable();
            $table->string('font_head', 255)->nullable();

            $table->timestamps();
        });

        // Mapping a single theme to a user
        Schema::create('user_themes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->index();
            $table->unsignedBigInteger('theme_id')->index();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();

            $table->foreign('theme_id')
                  ->references('id')->on('themes')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_themes');
        Schema::dropIfExists('themes');
    }
};
