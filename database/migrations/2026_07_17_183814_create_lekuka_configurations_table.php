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
        Schema::create('lekuka_configurations', function (Blueprint $table) {

        $table->id();

        $table->foreignId('company_id')->constrained();

        $table->foreignId('branch_id')->constrained();

        $table->foreignId('device_id')
            ->constrained('lekuka_devices');

        $table->string('operation_id')->nullable();

        $table->string('device_serial_no')->nullable();

        $table->string('device_operating_mode')->nullable();

        $table->string('taxpayer_name');

        $table->string('taxpayer_tin');

        $table->string('vat_number')->nullable();

        $table->string('branch_name');

        $table->json('branch_address')->nullable();

        $table->json('branch_contacts')->nullable();

        $table->integer('device_reporting_frequency')->default(15);

        $table->integer('taxpayer_day_max_hours')->default(24);

        $table->integer('day_end_notification_hours')->default(2);

        $table->json('receipt_forms')->nullable();

        $table->json('taxes')->nullable();

        $table->json('payment_types')->nullable();

        $table->json('raw_response')->nullable();

        $table->timestamp('downloaded_at');

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lekuka_configurations');
    }
};
