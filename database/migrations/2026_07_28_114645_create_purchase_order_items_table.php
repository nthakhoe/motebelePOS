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
        Schema::create('purchase_order_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('purchase_order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('ordered_quantity',18,2);

            $table->decimal('received_quantity',18,2)
                ->default(0);

            $table->decimal('remaining_quantity',18,2);

            $table->decimal('unit_cost',18,2);

            $table->decimal('discount',18,2)
                ->default(0);

            $table->decimal('tax',18,2)
                ->default(0);

            $table->decimal('line_total',18,2);

            $table->text('remarks')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
