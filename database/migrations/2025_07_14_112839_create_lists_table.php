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
        // 1) Create 'lists' table
        Schema::create('lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()       // references users.id
                  ->cascadeOnDelete();  // delete lists if user is removed
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_active')
                  ->default(true)
                  ->comment('true = active, false = inactive');
            $table->timestamps();
        });

        // 2) Create 'list_users' table
        Schema::create('list_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')
                  ->constrained('lists')
                  ->cascadeOnDelete();  // delete subscribers if list is deleted
            $table->uuid('user_uuid')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->boolean('is_active')
                  ->default(true)
                  ->comment('true = active, false = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_users');
        Schema::dropIfExists('lists');
    }
};
