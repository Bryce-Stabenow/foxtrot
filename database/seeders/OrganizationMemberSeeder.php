<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizationMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the organization created by OrganizationSeeder
        $organization = Organization::where('name', 'Foxtrot Organization')->first();

        if (!$organization) {
            return;
        }

        // Create some additional members for testing
        $members = [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'user_type' => UserType::MEMBER,
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'user_type' => UserType::MEMBER,
            ],
            [
                'name' => 'Carol Davis',
                'email' => 'carol@example.com',
                'user_type' => UserType::MEMBER,
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david@example.com',
                'user_type' => UserType::MEMBER,
            ],
            [
                'name' => 'Eva Brown',
                'email' => 'eva@example.com',
                'user_type' => UserType::MEMBER,
            ],
            [
                'name' => 'Frank Owner',
                'email' => 'frank@example.com',
                'user_type' => UserType::OWNER,
            ],
            [
                'name' => 'Grace Admin',
                'email' => 'grace@example.com',
                'user_type' => UserType::ADMIN,
            ],
        ];

        foreach ($members as $memberData) {
            User::firstOrCreate(
                ['email' => $memberData['email']],
                [
                    'name' => $memberData['name'],
                    'email' => $memberData['email'],
                    'password' => Hash::make('password'),
                    'organization_id' => $organization->id,
                    'user_type' => $memberData['user_type'],
                ]
            );
        }
    }
} 