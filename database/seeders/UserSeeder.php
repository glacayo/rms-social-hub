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
        // 1. Create Users
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@rms.test',
            'password' => bcrypt('password'),
            'role' => 'super-admin',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@rms.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $editor = User::create([
            'name' => 'Editor User',
            'email' => 'editor@rms.test',
            'password' => bcrypt('password'),
            'role' => 'editor',
        ]);

        // 2. Create Facebook Pages using Factory
        $pages = \App\Models\FacebookPage::factory()->count(5)->create([
            'linked_by_user_id' => $superAdmin->id,
        ]);

        // 3. Assign Permissions to Super Admin (all pages)
        foreach ($pages as $page) {
            \App\Models\UserPagePermission::create([
                'user_id' => $superAdmin->id,
                'page_id' => $page->id,
                'assigned_by' => $superAdmin->id,
            ]);
        }

        // 4. Assign some Permissions to Admin (first 2 pages)
        for ($i = 0; $i < 2; $i++) {
            \App\Models\UserPagePermission::create([
                'user_id' => $admin->id,
                'page_id' => $pages[$i]->id,
                'assigned_by' => $superAdmin->id,
            ]);
        }

        // 5. Create Sample Posts for Super Admin
        \App\Models\Post::factory()->count(10)->create([
            'user_id' => $superAdmin->id,
        ]);
        
        // 6. Create some Scheduled Posts
        \App\Models\Post::factory()->scheduled()->count(3)->create([
            'user_id' => $superAdmin->id,
        ]);
    }
}
