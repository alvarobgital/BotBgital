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
            $table->string('response_type')->default('text')->change();
        });
    }

    public function down(): void
    {
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->enum('response_type', ['text', 'buttons', 'handoff', 'list', 'ticket_creation'])->default('text')->change();
        });
    }
};
