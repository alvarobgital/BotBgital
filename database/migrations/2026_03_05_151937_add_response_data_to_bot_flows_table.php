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
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->json('response_data')->after('response_buttons')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->dropColumn('response_data');
        });
    }
};
