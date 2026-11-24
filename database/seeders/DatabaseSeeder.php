<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role_id' => Role::where('name', 'admin')->value('id'),
        ]);

        User::factory()->create([
            'name' => 'Pemilik User',
            'email' => 'pemilik@example.com',
            'whatsapp_number' => '08123456789',
            'whatsapp_verified_at' => now(),
            'role_id' => Role::where('name', 'pemilik_kost')->value('id'),
        ]);

        User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'role_id' => Role::where('name', 'customer')->value('id'),
        ]);
    }
}
