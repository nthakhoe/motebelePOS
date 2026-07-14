<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {

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

            $table->foreignId('register_session_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | User
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Movement
            |--------------------------------------------------------------------------
            */

            $table->string('reference_no')->unique();

            $table->enum('movement_type',[
                'Opening Float',
                'Cash In',
                'Cash Out',
                'Expense',
                'Safe Drop',
                'Bank Deposit',
                'Cash Pickup',
                'Adjustment'
            ]);

            $table->enum('direction',[
                'IN',
                'OUT'
            ]);

            $table->decimal('amount',15,2);

            $table->string('reason');

            $table->text('notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Approval
            |--------------------------------------------------------------------------
            */

            $table->boolean('requires_approval')
                ->default(false);

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users');

            $table->timestamp('approved_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Supporting Document
            |--------------------------------------------------------------------------
            */

            $table->string('attachment')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status',[
                'Pending',
                'Approved',
                'Rejected'
            ])->default('Approved');

            $table->timestamps();

            $table->index([
                'register_session_id',
                'movement_type'
            ]);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};