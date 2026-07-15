<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@clint-rono.dev')],
            [
                'name' => env('ADMIN_NAME', 'Clinton Rono'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeThisPassword123!')),
            ]
        );

        $this->call(AgentSeeder::class);
    }
}
