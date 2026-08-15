<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('refund_id')
                ->constrained('refunds')
                ->cascadeOnDelete();

            $table->foreignId('sale_item_id')
                ->constrained('sale_items')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            /*
             * Snapshot fields.
             *
             * These are intentionally stored here so that the
             * refund audit remains understandable even if the
             * product is later renamed or deleted.
             */

            $table->string('product_name');

            $table->string('sku')->nullable();

            $table->decimal('quantity', 19, 4);

            $table->decimal('unit_price', 19, 2);

            $table->decimal('refund_amount', 19, 2);

            $table->text('reason')->nullable();

            $table->timestamps();

            $table->index('sale_item_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_items');
    }
};