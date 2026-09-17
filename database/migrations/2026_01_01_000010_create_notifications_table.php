<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // Null user_id + non-null audience = platform broadcast (from admin).
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('audience', ['all', 'customers', 'mechanics'])->nullable();
            $table->string('title');
            $table->text('body');
            $table->string('kind')->default('info'); // booking, message, promo, announcement, review, system...
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
