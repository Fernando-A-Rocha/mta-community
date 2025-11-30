<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // For now call the user seeder (useful for development)
        $this->call([
            UserSeeder::class,
        ]);
    }
}
