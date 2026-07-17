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
        Schema::create('lekuka_fiscal_days', function (Blueprint $table) {

        $table->id();

        $table->foreignId('company_id')->constrained();

        $table->foreignId('branch_id')->constrained();

        $table->foreignId('device_id')
            ->constrained('lekuka_devices');

        $table->uuid('correlation_id');

        $table->string('fiscal_day_no');

        $table->date('business_date');

        $table->timestamp('opened_at')->nullable();

        $table->timestamp('closed_at')->nullable();

        $table->enum('status',[
            'OPEN',
            'CLOSED'
        ]);

        $table->json('response')->nullable();

        $table->timestamps();

        $table->index([
            'device_id',
            'business_date'
        ]);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lekuka_fiscal_days');
    }
};
