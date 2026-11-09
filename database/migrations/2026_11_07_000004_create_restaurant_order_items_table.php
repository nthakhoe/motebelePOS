<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_order_id')->constrained('restaurant_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            // Snapshots preserve the order if the linked product is later renamed or deleted.
            $table->string('product_name', 255);
            $table->string('sku', 100)->nullable();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('tax_rate', 7, 4)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('special_instructions')->nullable();
            $table->timestamps();

            $table->index(['restaurant_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_order_items');
    }
};
