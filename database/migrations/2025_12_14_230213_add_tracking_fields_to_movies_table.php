<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->integer('total_attendance')->default(0)->after('listing_end');
            $table->integer('view_count')->default(0)->after('total_attendance');
            $table->decimal('popularity_score', 8, 2)->default(0)->after('view_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['total_attendance', 'view_count', 'popularity_score']);
        });
    }
};
