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
        Schema::create('lekuka_receipts', function (Blueprint $table) {

        $table->id();

        $table->foreignId('company_id')->constrained();

        $table->foreignId('branch_id')->constrained();

        $table->foreignId('device_id')
            ->constrained('lekuka_devices');

        $table->foreignId('sale_id')
            ->constrained();

        $table->uuid('correlation_id');

        $table->string('receipt_number')->nullable();

        $table->string('receipt_global_no')->nullable();

        $table->string('receipt_counter')->nullable();

        $table->string('fiscal_day_no')->nullable();

        $table->string('qr_code')->nullable();

        $table->string('verification_code')->nullable();

        $table->string('server_signature')->nullable();

        $table->enum('status',[
            'PENDING',
            'SUBMITTED',
            'FAILED'
        ]);

        $table->json('request');

        $table->json('response')->nullable();

        $table->text('error_message')->nullable();

        $table->timestamp('submitted_at')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lekuka_receipts');
    }
};
