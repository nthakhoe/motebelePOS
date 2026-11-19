<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Lekuka Devices
        |--------------------------------------------------------------------------
        */

        Schema::table('lekuka_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('lekuka_devices', 'last_receipt_counter')) {
                $table->integer('last_receipt_counter')->default(1);
            }

            if (!Schema::hasColumn('lekuka_devices', 'last_global_receipt_no')) {
                $table->integer('last_global_receipt_no')->default(1);
            }
        });

        /*
        |--------------------------------------------------------------------------
        | 2. Lekuka Fiscal Days
        |--------------------------------------------------------------------------
        */

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE lekuka_configurations
                MODIFY branch_id BIGINT UNSIGNED NULL,
                MODIFY taxpayer_name VARCHAR(255) NULL,
                MODIFY taxpayer_tin VARCHAR(255) NULL,
                MODIFY branch_name VARCHAR(255) NULL,
                MODIFY downloaded_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP
            ");
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Lekuka Receipts
        |--------------------------------------------------------------------------
        */

        Schema::table('lekuka_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('lekuka_receipts', 'DeviceId')) {
                $table->integer('DeviceId')->nullable();
            }

            if (!Schema::hasColumn('lekuka_receipts', 'ReceiptId')) {
                $table->bigInteger('ReceiptId')->nullable();
            }

            if (!Schema::hasColumn('lekuka_receipts', 'InvoiceNo')) {
                $table->string('InvoiceNo', 100)->nullable();
            }

            if (!Schema::hasColumn('lekuka_receipts', 'device_hash')) {
                $table->longText('device_hash')->nullable();
            }

            if (!Schema::hasColumn('lekuka_receipts', 'device_signature')) {
                $table->longText('device_signature')->nullable();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | 4. Lekuka Configurations
        |--------------------------------------------------------------------------
        */

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE lekuka_configurations
                MODIFY branch_id BIGINT UNSIGNED NULL,
                MODIFY taxpayer_name VARCHAR(255) NULL,
                MODIFY taxpayer_tin VARCHAR(255) NULL,
                MODIFY branch_name VARCHAR(255) NULL,
                MODIFY downloaded_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP
            ");
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'This migration is intentionally irreversible. ' .
            'It reconciles the Lekuka schema and must not remove ' .
            'columns or modify existing fiscal records.'
        );
    }
};