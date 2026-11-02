<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CategorySeeder::class,
        ]);

        // Membuat atau menimpa akun admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        // Membuat atau menimpa akun author
        $author = User::updateOrCreate(
            ['email' => 'author@example.com'],
            [
                'name' => 'Author User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $author->syncRoles(['author']);
    }
}
