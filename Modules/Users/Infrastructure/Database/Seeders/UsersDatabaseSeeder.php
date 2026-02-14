<?php

namespace Modules\Users\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

class UsersDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user with admin privileges
        UserModel::create([
            'name' => 'Admin User',
            'email' => 'admin@faridlabs.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        // Create 10 sample users with verified emails
        UserModel::factory()
            ->count(10)
            ->verified()
            ->create();
    }
}
