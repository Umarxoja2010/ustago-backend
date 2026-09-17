<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Weekly payout');
            $table->unsignedBigInteger('amount');
            $table->date('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
