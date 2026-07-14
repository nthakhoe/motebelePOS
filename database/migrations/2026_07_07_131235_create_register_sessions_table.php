<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('register_sessions', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Ownership
            |--------------------------------------------------------------------------
            */

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('terminal_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Session
            |--------------------------------------------------------------------------
            */

            $table->string('session_number')->unique();

            $table->enum('status',[
                'Open',
                'Closed',
                'Suspended'
            ])->default('Open');

            /*
            |--------------------------------------------------------------------------
            | Opening
            |--------------------------------------------------------------------------
            */

            $table->decimal('opening_float',15,2)->default(0);

            $table->timestamp('opened_at');

            $table->foreignId('opened_by')
                ->constrained('users');

            /*
            |--------------------------------------------------------------------------
            | Closing
            |--------------------------------------------------------------------------
            */

            $table->decimal('closing_amount',15,2)
                ->nullable();

            $table->decimal('expected_amount',15,2)
                ->nullable();

            $table->decimal('cash_difference',15,2)
                ->nullable();

            $table->text('closing_notes')
                ->nullable();

            $table->timestamp('closed_at')
                ->nullable();

            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users');

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */

            $table->decimal('cash_sales',15,2)
                ->default(0);

            $table->decimal('card_sales',15,2)
                ->default(0);

            $table->decimal('mobile_sales',15,2)
                ->default(0);

            $table->decimal('bank_sales',15,2)
                ->default(0);

            $table->decimal('credit_sales',15,2)
                ->default(0);

            $table->decimal('refund_total',15,2)
                ->default(0);

            $table->decimal('discount_total',15,2)
                ->default(0);

            $table->decimal('tax_total',15,2)
                ->default(0);

            $table->decimal('gross_sales',15,2)
                ->default(0);

            $table->decimal('net_sales',15,2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Counters
            |--------------------------------------------------------------------------
            */

            $table->integer('transaction_count')
                ->default(0);

            $table->integer('receipt_count')
                ->default(0);

            $table->integer('refund_count')
                ->default(0);

            $table->timestamps();

            $table->index([
                'company_id',
                'branch_id',
                'terminal_id',
                'status'
            ]);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('register_sessions');
    }
};