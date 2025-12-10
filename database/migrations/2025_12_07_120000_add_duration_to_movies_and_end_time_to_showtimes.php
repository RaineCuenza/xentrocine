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
        // Add duration (minutes) to movies
        Schema::table('movies', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration')->default(90)->after('audience');
        });

        // Add end_time to showtimes; keep existing 'showtime' as start_time
        Schema::table('showtimes', function (Blueprint $table) {
            $table->time('end_time')->nullable()->after('showtime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('duration');
        });
    }
};
