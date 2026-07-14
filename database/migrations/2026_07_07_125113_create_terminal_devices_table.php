<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_devices', function (Blueprint $table) {

            $table->id();

            $table->foreignId('terminal_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            /*
             |--------------------------------------------------------------------------
             | Lekuka Registration
             |--------------------------------------------------------------------------
             */

            $table->string('device_id')->nullable();

            $table->string('activation_key')->nullable();

            $table->string('device_model')->nullable();

            $table->string('device_version')->nullable();

            $table->string('device_serial')->nullable();

            /*
             |--------------------------------------------------------------------------
             | Certificate
             |--------------------------------------------------------------------------
             */

            $table->string('certificate_path')->nullable();

            $table->string('private_key_path')->nullable();

            $table->string('certificate_thumbprint')->nullable();

            $table->timestamp('certificate_expiry')->nullable();

            /*
             |--------------------------------------------------------------------------
             | Device
             |--------------------------------------------------------------------------
             */

            $table->enum('operating_mode',[
                'Online',
                'Offline'
            ])->default('Online');

            $table->boolean('registered')
                ->default(false);

            /*
             |--------------------------------------------------------------------------
             | Fiscal
             |--------------------------------------------------------------------------
             */

            $table->integer('last_fiscal_day')
                ->default(0);

            $table->integer('last_receipt_counter')
                ->default(0);

            $table->integer('last_receipt_global')
                ->default(0);

            $table->timestamp('last_sync')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_devices');
    }
};