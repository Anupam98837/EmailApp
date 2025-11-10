<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // First, modify the existing status enum to include the new statuses
            $table->enum('status', [
                'scheduled', 
                'running', 
                'completed', 
                'waiting',
                'paused',
                'stopped'
            ])
            ->default('scheduled')
            ->change()
            ->comment('High-level lifecycle state: scheduled|running|completed|waiting|paused|stopped');
        });

        // Update existing campaigns that might need status changes
        // For example, if you want to set all completed campaigns that have_run = true
        DB::table('campaigns')
            ->where('has_run', true)
            ->where('status', 'running')
            ->update(['status' => 'completed']);
    }

    public function down(): void
    {
        // First, update any campaigns with new statuses back to old statuses
        DB::table('campaigns')
            ->whereIn('status', ['paused', 'stopped'])
            ->update(['status' => 'scheduled']);

        // Then modify the enum back to original values
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('status', [
                'scheduled', 
                'running', 
                'completed', 
                'waiting'
            ])
            ->default('scheduled')
            ->change()
            ->comment('High-level lifecycle state: scheduled|running|completed|waiting');
        });
    }
};