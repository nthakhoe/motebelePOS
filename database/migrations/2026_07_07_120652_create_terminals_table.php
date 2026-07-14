<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminals', function (Blueprint $table) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('terminal_name');

            $table->string('terminal_code')->unique();

            $table->string('serial_number')->nullable();

            $table->string('computer_name')->nullable();

            $table->string('ip_address')->nullable();

            $table->string('mac_address')->nullable();

            $table->enum('status',[
                'Available',
                'Busy',
                'Offline',
                'Maintenance',
                'Disabled'
            ])->default('Available');

            $table->boolean('default_terminal')->default(false);

            $table->boolean('is_active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['company_id','branch_id']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminals');
    }
};