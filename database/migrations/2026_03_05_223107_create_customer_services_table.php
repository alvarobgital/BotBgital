<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('customer_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('account_number')->unique();
            $table->string('plan_name')->nullable();
            $table->string('label')->nullable(); // "Casa", "Oficina", "Sucursal Centro"
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('customer_id');
        });

        // Migrate existing data from customers table
        $customers = \Illuminate\Support\Facades\DB::table('customers')->get();
        foreach ($customers as $customer) {
            if ($customer->account_number) {
                \Illuminate\Support\Facades\DB::table('customer_services')->insert([
                    'customer_id' => $customer->id,
                    'account_number' => $customer->account_number,
                    'plan_name' => null,
                    'label' => $customer->service_name,
                    'is_active' => $customer->is_active,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                ]);
            }
        }

        // Remove migrated columns from customers
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['account_number', 'service_name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('account_number')->nullable()->unique();
            $table->string('service_name')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::dropIfExists('customer_services');
    }
};
