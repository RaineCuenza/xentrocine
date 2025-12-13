<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cinema;

class CinemaSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent seed: update-or-create by name
        $items = [
            ['name' => 'Cinema 1', 'capacity' => 120, 'status' => 'available'],
            ['name' => 'Cinema 2', 'capacity' => 120, 'status' => config('app.cinema2_available') ? 'available' : 'maintenance'],
        ];

        foreach ($items as $item) {
            Cinema::updateOrCreate(
                ['name' => $item['name']],
                ['capacity' => $item['capacity'], 'status' => $item['status']]
            );
        }
    }
}
