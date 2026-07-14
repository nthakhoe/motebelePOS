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
        Schema::create('sale_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('sale_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained()
                ->restrictOnDelete();

            // Quantity Sold
            $table->decimal('quantity', 18, 3);

            // Selling Price
            $table->decimal('unit_price', 18, 2);

            // Discount per line
            $table->decimal('discount', 18, 2)->default(0);

            // VAT
            $table->decimal('tax_rate', 8, 2)->default(0);

            $table->decimal('tax_amount', 18, 2)->default(0);

            // Line Total
            $table->decimal('line_total', 18, 2);

            // Cost Price at the time of sale
            $table->decimal('cost_price', 18, 2)->default(0);

            // Profit on this line
            $table->decimal('profit', 18, 2)->default(0);

            // Remarks
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('sale_id');
            $table->index('product_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};