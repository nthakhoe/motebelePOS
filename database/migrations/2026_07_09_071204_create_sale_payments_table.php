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
        Schema::create('sale_payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('sale_id')
                ->constrained()
                ->cascadeOnDelete();

            // Payment Method
            $table->enum('payment_method', [
                'cash',
                'card',
                'mobile_money',
                'bank_transfer',
                'credit',
                'gift_voucher',
                'cheque',
                'other',
            ]);

            // Amount Paid
            $table->decimal('amount', 18, 2);

            // Reference Number
            $table->string('reference_number')->nullable();

            // Card / Mobile / Bank Provider
            $table->string('provider')->nullable();

            // Payment Date
            $table->timestamp('paid_at')->nullable();

            // Cashier
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Status
            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'reversed',
            ])->default('completed');

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('sale_id');
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};