<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->unsignedTinyInteger('capacity')->default(2);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'name']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained('room_types')->restrictOnDelete();
            $table->string('room_number', 20);
            $table->string('floor', 20)->nullable();
            $table->unsignedTinyInteger('capacity')->default(2);
            $table->decimal('price', 10, 2)->default(0);
            // available | occupied | reserved | cleaning | maintenance
            $table->string('status', 20)->default('available');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'room_number']);
            $table->index(['hotel_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
    }
};
