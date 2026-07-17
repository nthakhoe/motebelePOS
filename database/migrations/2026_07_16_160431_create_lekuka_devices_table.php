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
        Schema::create('lekuka_devices', function (Blueprint $table) {

        $table->id();

        $table->foreignId('company_id')->constrained();

        $table->foreignId('branch_id')->constrained();

        $table->unsignedInteger('device_id')->unique();

        $table->string('activation_key',20);

        $table->string('serial_number',50);

        $table->string('device_model');

        $table->string('device_model_version');

        $table->string('certificate_path')->nullable();

        $table->string('private_key_path')->nullable();

        $table->string('thumbprint')->nullable();

        $table->json('configuration')->nullable();

        $table->boolean('registered')->default(false);

        $table->timestamp('registered_at')->nullable();

        $table->timestamp('last_ping_at')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lekuka_devices');
    }
};
