<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5, enforced in FormRequest
            $table->text('comment')->nullable();
            $table->boolean('hidden')->default(false);
            $table->timestamps();

            $table->index(['master_profile_id', 'hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
