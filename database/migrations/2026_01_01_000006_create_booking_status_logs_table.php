<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Powers the customer-facing booking timeline
        // (requested -> confirmed -> in service -> completed).
        Schema::create('booking_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('label')->nullable();
            $table->timestamp('happened_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_logs');
    }
};
