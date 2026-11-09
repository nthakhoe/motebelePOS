<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->restrictOnDelete();
            $table->foreignId('waiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number', 40);
            $table->unsignedSmallInteger('guest_count')->default(1);
            $table->string('customer_name', 150)->nullable();
            $table->string('status', 30)->default('open');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('bill_requested_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'order_number'], 'restaurant_orders_branch_number_unique');
            $table->index(['company_id', 'branch_id', 'status'], 'restaurant_orders_branch_status_index');
            $table->index(['table_id', 'status']);
            $table->index(['waiter_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_orders');
    }
};
