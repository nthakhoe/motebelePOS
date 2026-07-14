<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->after('company_id')
                ->constrained()
                ->nullOnDelete();

            $table->string('employee_number')->nullable();

            $table->string('phone')->nullable();

            $table->string('profile_photo')->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamp('last_login_at')->nullable();

            $table->string('last_login_ip')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropConstrainedForeignId('company_id');
            $table->dropConstrainedForeignId('branch_id');

            $table->dropColumn([
                'employee_number',
                'phone',
                'profile_photo',
                'is_active',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};