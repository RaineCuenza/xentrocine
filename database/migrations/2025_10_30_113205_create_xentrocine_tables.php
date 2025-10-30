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
        // Users table
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->enum('role', ['client', 'admin'])->default('client');
            $table->timestamps();
            
            $table->index('email');
            $table->index('role');
        });

        // Movies table
        Schema::create('movies', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('title', 200);
            $table->decimal('price', 10, 2);
            $table->string('poster', 500)->nullable();
            $table->decimal('rating', 3, 1)->default(0);
            $table->timestamps();
            
            $table->index('title');
        });

        // Showtimes table
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->string('movie_id', 50);
            $table->time('showtime');
            $table->timestamps();
            
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');
            $table->index('movie_id');
        });

        // Bookings table
        Schema::create('bookings', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('user_id', 50)->nullable();
            $table->string('name', 100);
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('reference', 100)->nullable();
            $table->decimal('total', 10, 2);
            $table->enum('status', ['Pending', 'Paid', 'Cancelled'])->default('Paid');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });

        // Booking items table
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->string('booking_id', 50);
            $table->string('movie_id', 50);
            $table->string('movie_title', 200);
            $table->date('booking_date');
            $table->time('showtime');
            $table->string('cinema', 50);
            $table->integer('seat_row');
            $table->integer('seat_col');
            $table->string('seat_key', 20);
            $table->decimal('price', 10, 2);
            $table->timestamps();
            
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');
            $table->index('booking_id');
            $table->index('movie_id');
            $table->index(['movie_id', 'booking_date', 'showtime', 'cinema'], 'idx_session');
            $table->index(['seat_row', 'seat_col']);
            $table->unique(['movie_id', 'booking_date', 'showtime', 'cinema', 'seat_key'], 'unique_seat_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('showtimes');
        Schema::dropIfExists('movies');
        Schema::dropIfExists('users');
    }
};
