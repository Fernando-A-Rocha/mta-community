<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createUser('test1', 'test1@example.com', 'password');
        $this->createUser('test2', 'test2@example.com', 'password');
        // Admin
        $this->createUser('admin', 'admin@example.com', 'password', 'admin');
        // Moderator
        $this->createUser('moderator', 'moderator@example.com', 'password', 'moderator');
    }

    private function createUser(string $name, string $email, string $password, string $role = 'user'): void
    {
        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
        ]);
    }
}
