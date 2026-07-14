<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {

            $table->id();

            $table->string('company_name');
            $table->string('trading_name')->nullable();

            $table->string('tax_number')->nullable();
            $table->string('vat_number')->nullable();

            $table->string('registration_number')->nullable();

            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();

            $table->string('email')->nullable();

            $table->string('website')->nullable();

            $table->text('physical_address')->nullable();

            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('country')->default('Lesotho');

            $table->string('currency')->default('LSL');

            $table->string('timezone')->default('Africa/Maseru');

            $table->string('logo')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};