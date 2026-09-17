<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A master's own priced offering of a catalog service. Price and
        // duration are set per-master (matches "narx master tomonidan
        // boshqariladi" requirement).
        Schema::create('master_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('price');
            $table->string('duration')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['master_profile_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_services');
    }
};
