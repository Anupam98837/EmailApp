<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // If you are on MySQL 8+ you could use enum; simple string is flexible.
            $table->enum('status', ['scheduled','running','completed','waiting'])
                  ->default('scheduled')
                  ->after('has_run')
                  ->comment('High-level lifecycle state: scheduled|running|done');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
