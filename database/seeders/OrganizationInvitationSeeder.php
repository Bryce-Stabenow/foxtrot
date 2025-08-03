<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationInvitationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the Foxtrot organization specifically
        $organization = Organization::where('name', 'Foxtrot Organization')->first();
        $users = User::where('organization_id', $organization->id)->get();

        if (!$organization || $users->isEmpty()) {
            return;
        }

        // Create pending invitations
        OrganizationInvitation::factory()
            ->count(5)
            ->pending()
            ->create([
                'organization_id' => $organization->id,
                'invited_by_user_id' => $users->whereIn('user_type', [UserType::ADMIN, UserType::OWNER])->random()->id,
            ]);

        // Create accepted invitations
        OrganizationInvitation::factory()
            ->count(3)
            ->accepted()
            ->create([
                'organization_id' => $organization->id,
                'invited_by_user_id' => $users->whereIn('user_type', [UserType::ADMIN, UserType::OWNER])->random()->id,
            ]);

        // Create expired invitations
        OrganizationInvitation::factory()
            ->count(2)
            ->expired()
            ->create([
                'organization_id' => $organization->id,
                'invited_by_user_id' => $users->whereIn('user_type', [UserType::ADMIN, UserType::OWNER])->random()->id,
            ]);
    }
}
