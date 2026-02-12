<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Users\Infrastructure\Database\Seeders\UsersDatabaseSeeder;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsersDatabaseSeeder::class,
            // Add other module seeders here
            // \Modules\Workspace\Infrastructure\Database\Seeders\WorkspaceDatabaseSeeder::class,
        ]);
    }
}
