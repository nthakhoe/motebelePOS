<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('lekuka_tax_id')
                ->nullable()
                ->after('tax_rate');

            $table->string('lekuka_tax_type', 50)
                ->nullable()
                ->after('lekuka_tax_id');

            $table->string('lekuka_tax_code', 20)
                ->nullable()
                ->after('lekuka_tax_type');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedInteger('lekuka_tax_id')
                ->nullable()
                ->after('tax_rate');

            $table->string('lekuka_tax_type', 50)
                ->nullable()
                ->after('lekuka_tax_id');

            $table->string('lekuka_tax_code', 20)
                ->nullable()
                ->after('lekuka_tax_type');
        });

        // Assign Tax ID 7 to existing taxable goods.
        DB::table('products')
            ->where('is_service', false)
            ->where('tax_rate', 15.00)
            ->update([
                'lekuka_tax_id' => 7,
                'lekuka_tax_type' => 'VAT',
                'lekuka_tax_code' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'lekuka_tax_id',
                'lekuka_tax_type',
                'lekuka_tax_code',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'lekuka_tax_id',
                'lekuka_tax_type',
                'lekuka_tax_code',
            ]);
        });
    }
};