<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->constrained('restaurant_areas')->restrictOnDelete();
            $table->string('table_number', 30);
            $table->unsignedSmallInteger('capacity')->default(2);
            $table->string('status', 20)->default('available');
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'table_number'], 'restaurant_tables_branch_number_unique');
            $table->index(['company_id', 'branch_id', 'area_id', 'status'], 'restaurant_tables_floor_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
