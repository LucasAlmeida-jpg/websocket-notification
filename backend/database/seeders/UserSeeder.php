<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory()->create([
            'name'     => 'Alice',
            'email'    => 'alice@example.com',
            'password' => bcrypt('password'),
        ]);

        \App\Models\User::factory()->create([
            'name'     => 'Bob',
            'email'    => 'bob@example.com',
            'password' => bcrypt('password'),
        ]);

        \App\Models\User::factory()->create([
            'name'     => 'Carol',
            'email'    => 'carol@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}
