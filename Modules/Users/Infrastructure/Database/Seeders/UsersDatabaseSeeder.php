<?php

namespace Modules\Users\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Domain\Entities\User;
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

        // Create sample users
        User::factory()->count(10)->create();
    }
}