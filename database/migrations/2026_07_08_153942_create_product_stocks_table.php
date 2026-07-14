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
        Schema::create('product_stocks', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Ownership
            |--------------------------------------------------------------------------
            */

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Current Stock
            |--------------------------------------------------------------------------
            */

            $table->decimal('quantity_on_hand', 15, 3)
                ->default(0);

            $table->decimal('quantity_reserved', 15, 3)
                ->default(0);

            $table->decimal('quantity_available', 15, 3)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Costing
            |--------------------------------------------------------------------------
            */

            $table->decimal('average_cost', 15, 2)
                ->default(0);

            $table->decimal('last_cost', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Replenishment
            |--------------------------------------------------------------------------
            */

            $table->decimal('minimum_stock', 15, 3)
                ->default(0);

            $table->decimal('maximum_stock', 15, 3)
                ->default(0);

            $table->decimal('reorder_level', 15, 3)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Dates
            |--------------------------------------------------------------------------
            */

            $table->timestamp('last_received_at')
                ->nullable();

            $table->timestamp('last_sold_at')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Constraints
            |--------------------------------------------------------------------------
            */

            $table->unique([
                'company_id',
                'branch_id',
                'product_id'
            ]);

            $table->index([
                'company_id',
                'branch_id'
            ]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};