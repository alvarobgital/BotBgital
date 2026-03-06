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
        Schema::create('coverage_areas', function (Blueprint $table) {
            $table->id();
            $table->string('city')->default('México');
            $table->string('neighborhood'); // Colonia
            $table->string('zip_code');
            $table->text('streets')->nullable(); // Optional specific streets
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_areas');
    }
};
