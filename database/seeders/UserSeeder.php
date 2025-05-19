<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the organization
        $organization = Organization::where('name', 'Foxtrot Organization')->first();

        // Create main test user
        User::create([
            'name' => 'Jakus Allof',
            'email' => 'test@example.com',
            'password' => Hash::make('2TgvL8VDJkY53KE'),
            'organization_id' => $organization->id,
            'user_type' => UserType::MEMBER,
        ]);

        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('2TgvL8VDJkY53KE'),
            'organization_id' => $organization->id,
            'user_type' => UserType::ADMIN,
        ]);
    }
}
