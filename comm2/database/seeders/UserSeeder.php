<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * OPTIONAL SEEDER FOR TESTING
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = '!Super25u8StrongPW$';
        $this->createUser('test1', 'test1@example.com', $password);
        $this->createUser('test2', 'test2@example.com', $password);
        $this->createUser('admin', 'admin@example.com', $password, 'admin');
        $this->createUser('moderator', 'moderator@example.com', $password, 'moderator');
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
