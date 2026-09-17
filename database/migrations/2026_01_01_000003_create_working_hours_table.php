<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0 = Monday ... 6 = Sunday
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('closed')->default(false);
            $table->timestamps();

            $table->unique(['master_profile_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};
