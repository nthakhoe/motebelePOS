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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();

            // Company (Multi-tenant)
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            // Product being adjusted
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            // User performing the adjustment
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Adjustment details
            $table->enum('adjustment_type', [
                'increase',
                'decrease'
            ]);

            $table->decimal('quantity', 12, 2);

            $table->decimal('stock_before', 12, 2);

            $table->decimal('stock_after', 12, 2);

            $table->string('reference_no')->nullable();

            $table->text('reason')->nullable();

            $table->timestamp('adjustment_date')->useCurrent();

            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('product_id');
            $table->index('adjustment_date');
            $table->index('adjustment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};