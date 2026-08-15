<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('refund_number')->unique();

            $table->decimal('total_amount', 19, 2);

            $table->string('refund_method')->nullable();

            $table->string('reference_number')->nullable();

            $table->string('reason')->nullable();

            $table->text('remarks')->nullable();

            $table->string('status')
                ->default('completed');

            $table->timestamp('refunded_at');

            $table->timestamps();

            $table->index([
                'sale_id',
                'created_at',
            ]);

            $table->index('refund_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};