<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'region')) {
                $table->string('region')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('region');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('users', 'region')) {
                $drop[] = 'region';
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
