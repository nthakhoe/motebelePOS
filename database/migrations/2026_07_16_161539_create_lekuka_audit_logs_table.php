<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lekuka_audit_logs', function (Blueprint $table) {

            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('device_id')
                ->nullable()
                ->constrained('lekuka_devices')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('sale_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->uuid('correlation_id')->index();

            $table->string('action',100);

            $table->string('endpoint',255);

            $table->string('http_method',10);

            $table->integer('http_status')
                ->nullable();

            $table->enum('status',[
                'SUCCESS',
                'FAILED',
                'PENDING'
            ]);

            $table->json('request')
                ->nullable();

            $table->json('response')
                ->nullable();

            $table->text('error_message')
                ->nullable();

            $table->decimal('duration_ms',10,2)
                ->nullable();

            $table->ipAddress('ip_address')
                ->nullable();

            $table->string('reference_no')
                ->nullable();

            $table->timestamps();

            $table->index('action');
            $table->index('status');
            $table->index('reference_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lekuka_audit_logs');
    }
};