<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('bot_flows', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->json('trigger_keywords');
            $table->text('response_text');
            $table->enum('response_type', ['text', 'buttons', 'list', 'handoff', 'ticket_creation'])->default('text');
            $table->json('response_buttons')->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_flows');
    }
};
