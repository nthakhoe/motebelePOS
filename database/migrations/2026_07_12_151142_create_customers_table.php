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
        Schema::create('customers', function (Blueprint $table) {

            $table->id();

            // Company (Multi-tenant)
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            // Customer Information
            $table->string('customer_code')->unique();

            $table->enum('customer_type', [
                'individual',
                'business'
            ])->default('individual');

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('business_name')->nullable();

            // Contact Details
            $table->string('phone', 20)->nullable();
            $table->string('alternative_phone', 20)->nullable();
            $table->string('email')->nullable();

            // Identification
            $table->string('id_number')->nullable();
            $table->string('tax_number')->nullable();

            // Address
            $table->string('country')->default('Lesotho');
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->text('physical_address')->nullable();

            // Financial Information
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);

            // POS Flags
            $table->boolean('is_walk_in')->default(false);
            $table->boolean('allow_credit')->default(false);
            $table->boolean('is_active')->default(true);

            // Additional Information
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('customer_code');
            $table->index('phone');
            $table->index('business_name');
            $table->index('last_name');
            $table->index('customer_type');
            $table->index('is_active');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};