<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {

            $table->id();

            // Multi-company support
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Branch (future)
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();


            // Cashier
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Walk-in customer defaults to customer #1
            $table->foreignId('customer_id')->default(1)->constrained();

            // Receipt Number
            $table->string('sale_number')->unique();

            // Totals
            $table->decimal('subtotal',18,2)->default(0);
            $table->decimal('discount',18,2)->default(0);
            $table->decimal('tax',18,2)->default(0);
            $table->decimal('total',18,2)->default(0);

            // Amount Paid
            $table->decimal('amount_paid',18,2)->default(0);

            // Balance / Change
            $table->decimal('change',18,2)->default(0);

            // Sale Type
            $table->enum('sale_type',[
                'cash',
                'credit',
                'layby',
                'online'
            ])->default('cash');

            // Status
            $table->enum('status',[
                'draft',
                'completed',
                'cancelled',
                'refunded'
            ])->default('draft');

            // Lekuka
            $table->boolean('submitted_to_lekuka')->default(false);

            $table->string('lekuka_receipt_id')->nullable();

            $table->string('qr_code')->nullable();

            // Notes
            $table->text('remarks')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
