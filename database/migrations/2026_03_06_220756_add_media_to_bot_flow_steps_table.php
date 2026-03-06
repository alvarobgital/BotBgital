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
        Schema::table('bot_flow_steps', function (Blueprint $table) {
            $table->string('media_path')->nullable()->after('message_text');
            $table->string('media_type')->nullable()->after('media_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_flow_steps', function (Blueprint $table) {
            $table->dropColumn(['media_path', 'media_type']);
        });
    }
};
