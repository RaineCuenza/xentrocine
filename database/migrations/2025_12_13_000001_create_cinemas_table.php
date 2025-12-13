<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cinemas')) {
            Schema::create('cinemas', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->unique();
                $table->unsignedInteger('capacity')->default(120);
                $table->enum('status', ['available', 'maintenance', 'unavailable', 'inactive'])->default('available');
                $table->timestamps();

                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cinemas');
    }
};
