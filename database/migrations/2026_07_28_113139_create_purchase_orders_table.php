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
        Schema::create('purchase_orders', function (Blueprint $table) {

        $table->id();

        $table->foreignId('company_id')->constrained()->cascadeOnDelete();

        $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

        $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();

        $table->string('purchase_order_no')->unique();

        $table->date('order_date');

        $table->date('expected_delivery_date')->nullable();

        $table->enum('status', [

            'Draft',
            'Submitted',
            'Approved',
            'Partially Received',
            'Received',
            'Cancelled',
            'Closed',

        ])->default('Draft');

        $table->decimal('subtotal',18,2)->default(0);

        $table->decimal('discount',18,2)->default(0);

        $table->decimal('tax',18,2)->default(0);

        $table->decimal('total',18,2)->default(0);

        $table->text('remarks')->nullable();

        $table->foreignId('created_by')->constrained('users');

        $table->foreignId('approved_by')->nullable()->constrained('users');

        $table->timestamp('approved_at')->nullable();

        $table->timestamps();

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
