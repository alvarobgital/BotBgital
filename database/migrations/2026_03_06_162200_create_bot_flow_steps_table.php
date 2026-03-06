<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        // 1. Add new columns to bot_flows
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('id');
            $table->string('flow_type')->default('keyword')->after('category'); // main, keyword
            $table->string('description')->nullable()->after('flow_type');
            $table->integer('flow_priority')->default(10)->after('sort_order');
        });

        // 2. Create bot_flow_steps
        Schema::create('bot_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_flow_id')->constrained('bot_flows')->cascadeOnDelete();
            $table->string('step_key'); // unique within flow e.g. "ask_name"
            $table->text('message_text');
            $table->string('response_type')->default('text'); // text, buttons, list, input, action_only
            $table->json('options')->nullable(); // [{id, title, description, next_step, next_flow, action}]
            $table->string('action_type')->nullable(); // validate_client, check_coverage, show_plans, show_plan_categories, escalate_agent, create_lead, notify_telegram, close_conversation
            $table->json('action_config')->nullable(); // extra config
            $table->string('next_step_default')->nullable(); // next step when no option matches (for input steps)
            $table->string('input_validation')->nullable(); // none, zip_code, account_number, phone, text, name
            $table->integer('retry_limit')->default(0); // 0 = unlimited
            $table->boolean('is_entry_point')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['bot_flow_id', 'step_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_flow_steps');
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->dropColumn(['slug', 'flow_type', 'description', 'flow_priority']);
        });
    }
};
