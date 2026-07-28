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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            // Multi-company
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Supplier Code
            $table->string('supplier_code')->unique();

            // Business Information
            $table->string('business_name');
            $table->string('contact_person')->nullable();

            // Contact Details
            $table->string('phone')->nullable();
            $table->string('alternative_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Tax
            $table->string('tin_number')->nullable();
            $table->string('vat_number')->nullable();

            // Address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('country')->default('Lesotho');

            // Banking
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('branch_name')->nullable();

            // Payment Terms
            $table->unsignedInteger('credit_days')->default(30);

            // Financial
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('current_balance', 18, 2)->default(0);

            // Status
            $table->boolean('is_active')->default(true);

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('supplier_code');
            $table->index('business_name');
            $table->index('phone');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
