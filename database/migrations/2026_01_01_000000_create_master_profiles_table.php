<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('workshop_name');
            $table->string('owner_name')->nullable();
            $table->string('address');
            $table->string('district')->nullable();
            $table->string('city')->default('Tashkent');
            $table->text('about')->nullable();
            $table->string('cover')->nullable();
            $table->string('logo')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'suspended'])
                ->default('pending')->index();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('jobs_count')->default(0);
            $table->boolean('is_open')->default(true);
            $table->timestamps();

            $table->index(['city', 'district']);
            $table->index(['lat', 'lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_profiles');
    }
};
