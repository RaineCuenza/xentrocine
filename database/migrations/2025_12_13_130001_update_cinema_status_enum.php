<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Temporarily relax SQL mode to avoid strict warnings being treated as errors
        try {
            $mode = DB::select("SELECT @@SESSION.sql_mode AS mode");
            $prev = isset($mode[0]->mode) ? $mode[0]->mode : '';
            DB::statement("SET SESSION sql_mode=''");
            
            DB::statement("UPDATE cinemas SET status = LOWER(IFNULL(status, 'available'))");
            DB::statement("UPDATE cinemas SET status = 'available' WHERE status NOT IN ('available','maintenance','inactive','unavailable') OR status IS NULL OR status = ''");
            DB::statement("ALTER TABLE cinemas MODIFY COLUMN status ENUM('available','maintenance','unavailable','inactive') NOT NULL DEFAULT 'available'");

            // Restore previous sql_mode
            DB::statement("SET SESSION sql_mode=?", [$prev]);
        } catch (\Throwable $e) {
            // Attempt alter anyway if mode switch fails
            DB::statement("UPDATE cinemas SET status = LOWER(IFNULL(status, 'available'))");
            DB::statement("UPDATE cinemas SET status = 'available' WHERE status NOT IN ('available','maintenance','inactive','unavailable') OR status IS NULL OR status = ''");
            DB::statement("ALTER TABLE cinemas MODIFY COLUMN status ENUM('available','maintenance','unavailable','inactive') NOT NULL DEFAULT 'available'");
        }
    }

    public function down(): void
    {
        // Revert to previous set without 'unavailable'
        DB::statement("ALTER TABLE cinemas MODIFY COLUMN status ENUM('available','maintenance','inactive') NOT NULL DEFAULT 'available'");
    }
};
