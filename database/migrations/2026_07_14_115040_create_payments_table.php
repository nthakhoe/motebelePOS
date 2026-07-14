<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();

            $table->foreignId('payment_method_id')->constrained();

            $table->foreignId('user_id')->constrained();

            $table->decimal('amount_due', 18, 2);

            $table->decimal('amount_received', 18, 2);

            $table->decimal('amount_paid', 18, 2);

            $table->decimal('change_amount', 18, 2)->default(0);

            $table->string('reference_number')->nullable();

            $table->string('authorization_code')->nullable();

            $table->string('status')->default('Completed');

            $table->timestamp('payment_date');

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};