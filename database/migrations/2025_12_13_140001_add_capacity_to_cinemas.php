<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('cinemas', 'capacity')) {
            Schema::table('cinemas', function (Blueprint $table) {
                $table->unsignedInteger('capacity')->default(120)->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cinemas', 'capacity')) {
            Schema::table('cinemas', function (Blueprint $table) {
                $table->dropColumn('capacity');
            });
        }
    }
};
