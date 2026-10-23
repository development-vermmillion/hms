<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per hotel / client. Every other table is scoped by hotel_id.
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('gst_number', 40)->nullable();
            $table->string('currency_symbol', 8)->default('₹');
            $table->decimal('tax_percent', 5, 2)->default(12);
            $table->string('invoice_prefix', 20)->default('INV-');
            $table->unsignedInteger('invoice_seq')->default(0);
            $table->text('invoice_footer')->nullable();
            $table->string('check_in_time', 5)->default('12:00');
            $table->string('check_out_time', 5)->default('11:00');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('employee_code', 30);
            $table->string('role', 20)->default('staff'); // owner | staff
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['hotel_id', 'employee_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('hotels');
    }
};
