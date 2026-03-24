<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@rms.test',
            'password' => bcrypt('password'),
            'role' => 'super-admin',
        ]);

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@rms.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Editor User',
            'email' => 'editor@rms.test',
            'password' => bcrypt('password'),
            'role' => 'editor',
        ]);
    }
}
