<?php

namespace Modules\Users\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@faridlabs.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create sample users using direct create
        foreach (range(1, 10) as $i) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
    }
}
