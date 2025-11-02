<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Movie;
use App\Models\Showtime;

class XentroCineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert default admin user
        User::create([
            'id' => 'u_admin001',
            'name' => 'Administrator',
            'email' => 'admin@admin',
            'password' => '@dmin_123', // Will be hashed automatically
            'role' => 'admin',
            'email_verified_at' => now(), // Mark as verified for seeded users
        ]);

        // Insert sample client users
        User::create([
            'id' => 'u_client001',
            'name' => 'John Doe',
            'email' => 'client@client',
            'password' => 'P@ssword_123',
            'role' => 'client',
            'email_verified_at' => now(), // Mark as verified for seeded users
        ]);

        User::create([
            'id' => 'u_client002',
            'name' => 'Jane Smith',
            'email' => 'client2@client',
            'password' => 'P@ssword_123',
            'role' => 'client',
            'email_verified_at' => now(), // Mark as verified for seeded users
        ]);

        // Insert staff user
        User::create([
            'id' => 'u_staff001',
            'name' => 'Maria Santos',
            'email' => 'staff@staff',
            'password' => 'St@ff_123',
            'role' => 'staff',
            'email_verified_at' => now(), // Mark as verified for seeded users
        ]);

        // Insert sample movies
        $movie1 = Movie::create([
            'id' => 'mv100',
            'title' => 'Star Quest',
            'price' => 300.00,
            'poster' => '/images/starquest.jpg',
            'audience' => 'PG',
        ]);

        $movie2 = Movie::create([
            'id' => 'mv200',
            'title' => 'The Last Laugh',
            'price' => 320.00,
            'poster' => '/images/thelastlaugh.jpg',
            'audience' => 'R-13',
        ]);

        $movie3 = Movie::create([
            'id' => 'mv300',
            'title' => 'Hidden Tide',
            'price' => 350.00,
            'poster' => '/images/hiddentide.jpg',
            'audience' => 'G',
        ]);

        // Insert showtimes for Star Quest
        Showtime::create(['movie_id' => 'mv100', 'showtime' => '13:00:00']);
        Showtime::create(['movie_id' => 'mv100', 'showtime' => '15:30:00']);
        Showtime::create(['movie_id' => 'mv100', 'showtime' => '18:00:00']);
        Showtime::create(['movie_id' => 'mv100', 'showtime' => '20:30:00']);

        // Insert showtimes for The Last Laugh
        Showtime::create(['movie_id' => 'mv200', 'showtime' => '12:00:00']);
        Showtime::create(['movie_id' => 'mv200', 'showtime' => '14:15:00']);
        Showtime::create(['movie_id' => 'mv200', 'showtime' => '17:00:00']);
        Showtime::create(['movie_id' => 'mv200', 'showtime' => '19:45:00']);
        Showtime::create(['movie_id' => 'mv200', 'showtime' => '21:30:00']);

        // Insert showtimes for Hidden Tide
        Showtime::create(['movie_id' => 'mv300', 'showtime' => '11:00:00']);
        Showtime::create(['movie_id' => 'mv300', 'showtime' => '13:45:00']);
        Showtime::create(['movie_id' => 'mv300', 'showtime' => '16:15:00']);
        Showtime::create(['movie_id' => 'mv300', 'showtime' => '18:45:00']);

        $this->command->info('XentroCine seed data created successfully!');
    }
}
