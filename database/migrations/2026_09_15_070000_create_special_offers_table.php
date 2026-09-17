<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('cta')->default('Batafsil');
            $table->text('image');
            $table->string('link')->nullable();
            $table->string('badge')->nullable();
            $table->string('accent')->default('from-primary to-info');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_offers');
    }
};
