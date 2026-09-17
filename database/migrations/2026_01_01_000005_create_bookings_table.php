<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // customer
            $table->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_service_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('time');
            $table->unsignedBigInteger('price'); // snapshot of master_service price at booking time
            $table->enum('status', ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])
                ->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Prevent double-booking the same master at the same date/time
            // while the slot is still pending or accepted.
            $table->index(['master_profile_id', 'date', 'time']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
