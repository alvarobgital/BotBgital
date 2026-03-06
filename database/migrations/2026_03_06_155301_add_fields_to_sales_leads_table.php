<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            // Drop old status enum and recreate with new values
            $table->string('client_type')->nullable()->after('plan_interest');
            $table->string('zip_code')->nullable()->after('client_type');
            $table->string('company_name')->nullable()->after('zip_code');
            $table->string('phone')->nullable()->after('company_name');
            $table->text('notes')->nullable()->after('phone');
            $table->string('source')->default('whatsapp')->after('notes');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('source');
        });

        // Update status enum to string to allow new values
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['client_type', 'zip_code', 'company_name', 'phone', 'notes', 'source', 'assigned_to']);
        });
    }
};
