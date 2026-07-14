<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_settings', function (Blueprint $table) {

            $table->id();

            $table->foreignId('terminal_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            /*
             |--------------------------------------------------------------------------
             | Receipt
             |--------------------------------------------------------------------------
             */

            $table->enum('receipt_width',['48mm','80mm'])
                ->default('80mm');

            $table->integer('receipt_copies')
                ->default(1);

            $table->boolean('auto_print_receipt')
                ->default(true);

            /*
             |--------------------------------------------------------------------------
             | Hardware
             |--------------------------------------------------------------------------
             */

            $table->boolean('barcode_scanner')
                ->default(true);

            $table->boolean('cash_drawer')
                ->default(true);

            $table->boolean('customer_display')
                ->default(false);

            /*
             |--------------------------------------------------------------------------
             | Printer
             |--------------------------------------------------------------------------
             */

            $table->string('printer_driver')
                ->nullable();

            $table->string('printer_name')
                ->nullable();

            /*
             |--------------------------------------------------------------------------
             | Payments
             |--------------------------------------------------------------------------
             */

            $table->boolean('allow_cash')
                ->default(true);

            $table->boolean('allow_card')
                ->default(true);

            $table->boolean('allow_mobile')
                ->default(true);

            $table->boolean('allow_bank_transfer')
                ->default(false);

            /*
             |--------------------------------------------------------------------------
             | POS Behaviour
             |--------------------------------------------------------------------------
             */

            $table->boolean('allow_negative_stock')
                ->default(false);

            $table->boolean('require_customer')
                ->default(false);

            $table->boolean('open_cash_drawer_after_sale')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_settings');
    }
};