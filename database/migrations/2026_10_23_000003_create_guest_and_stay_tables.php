<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('id_proof_type', 40)->nullable();
            $table->string('id_proof_number', 60)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['hotel_id', 'phone']);
        });

        // A "stay" is a reservation that becomes a check-in and then a check-out.
        Schema::create('stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            // reserved | checked_in | checked_out | cancelled
            $table->string('status', 20)->default('reserved');
            $table->date('arrival_date');
            $table->dateTime('check_in_at')->nullable();
            $table->date('expected_check_out');
            $table->dateTime('check_out_at')->nullable();
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->decimal('rate', 10, 2)->default(0); // per night
            $table->text('notes')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['hotel_id', 'status']);
            $table->index(['hotel_id', 'check_in_at']);
            $table->index(['hotel_id', 'check_out_at']);
        });

        Schema::create('amenity_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->decimal('replacement_price', 10, 2)->default(0); // charged per missing item
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hotel_id', 'name']);
        });

        // Items handed to a guest (towel, kettle, slippers, plates...) and checked back at check-out.
        Schema::create('stay_amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stay_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_item_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('qty_issued')->default(1);
            $table->unsignedSmallInteger('qty_returned')->nullable();
            $table->decimal('penalty_amount', 10, 2)->default(0);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stay_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stay_id')->constrained()->cascadeOnDelete();
            $table->string('description', 160);
            $table->decimal('amount', 10, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stay_charges');
        Schema::dropIfExists('stay_amenities');
        Schema::dropIfExists('amenity_items');
        Schema::dropIfExists('stays');
        Schema::dropIfExists('guests');
    }
};
