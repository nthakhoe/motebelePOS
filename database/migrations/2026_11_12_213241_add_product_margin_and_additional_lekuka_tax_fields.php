<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        Schema::table('products', function (Blueprint $table) {

            // Configurable markup calculated against cost price.
            $table->decimal('margin_percentage', 5, 2)
                ->default(30.00)
                ->after('cost_price');

            // Optional second Lekuka tax allocation.
            $table->unsignedInteger('lekuka_additional_tax_id')
                ->nullable()
                ->after('lekuka_tax_code');

            $table->string('lekuka_additional_tax_type', 50)
                ->nullable()
                ->after('lekuka_additional_tax_id');

            $table->string('lekuka_additional_tax_code', 20)
                ->nullable()
                ->after('lekuka_additional_tax_type');

            /*
             * Stores either a percentage rate or fixed levy value.
             * Interpretation depends on lekuka_additional_tax_type.
             */
            $table->decimal('lekuka_additional_tax_rate', 15, 4)
                ->nullable()
                ->after('lekuka_additional_tax_code');
        });


        /*
        |--------------------------------------------------------------------------
        | Sale Items
        |--------------------------------------------------------------------------
        |
        | These fields preserve the additional tax allocation that applied
        | when the sale was made, even if the product configuration changes.
        |
        */

        Schema::table('sale_items', function (Blueprint $table) {

            $table->unsignedInteger('lekuka_additional_tax_id')
                ->nullable()
                ->after('lekuka_tax_code');

            $table->string('lekuka_additional_tax_type', 50)
                ->nullable()
                ->after('lekuka_additional_tax_id');

            $table->string('lekuka_additional_tax_code', 20)
                ->nullable()
                ->after('lekuka_additional_tax_type');

            $table->decimal('lekuka_additional_tax_rate', 15, 4)
                ->nullable()
                ->after('lekuka_additional_tax_code');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {

            $table->dropColumn([
                'lekuka_additional_tax_id',
                'lekuka_additional_tax_type',
                'lekuka_additional_tax_code',
                'lekuka_additional_tax_rate',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {

            $table->dropColumn([
                'margin_percentage',
                'lekuka_additional_tax_id',
                'lekuka_additional_tax_type',
                'lekuka_additional_tax_code',
                'lekuka_additional_tax_rate',
            ]);
        });
    }
};