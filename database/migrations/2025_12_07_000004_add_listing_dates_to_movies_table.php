<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->date('listing_start')->nullable()->after('available_until');
            $table->date('listing_end')->nullable()->after('listing_start');
            $table->index(['listing_start', 'listing_end']);
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex(['listing_start', 'listing_end']);
            $table->dropColumn(['listing_start', 'listing_end']);
        });
    }
};
