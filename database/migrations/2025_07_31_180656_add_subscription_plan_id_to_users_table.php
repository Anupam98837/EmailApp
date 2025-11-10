<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // foreign key to subscription_plans.id
            $table->foreignId('subscription_plan_id')
                  ->nullable()
                  ->constrained('subscription_plans')
                  ->nullOnDelete();

            // store the plan title at time of assignment
            $table->string('subscription_plan_title')
                  ->nullable()
                  ->after('subscription_plan_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn('subscription_plan_id');

            $table->dropColumn('subscription_plan_title');
        });
    }
};
