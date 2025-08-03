<?php

namespace Database\Seeders;

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
        // Get existing organizations and users for seeding
        $organizations = Organization::all();
        $users = User::all();

        if ($organizations->isEmpty() || $users->isEmpty()) {
            return;
        }

        // Create pending invitations
        OrganizationInvitation::factory()
            ->count(5)
            ->pending()
            ->create([
                'organization_id' => $organizations->random()->id,
                'invited_by_user_id' => $users->where('user_type', 'admin')->random()->id,
            ]);

        // Create accepted invitations
        OrganizationInvitation::factory()
            ->count(3)
            ->accepted()
            ->create([
                'organization_id' => $organizations->random()->id,
                'invited_by_user_id' => $users->where('user_type', 'admin')->random()->id,
            ]);

        // Create expired invitations
        OrganizationInvitation::factory()
            ->count(2)
            ->expired()
            ->create([
                'organization_id' => $organizations->random()->id,
                'invited_by_user_id' => $users->where('user_type', 'admin')->random()->id,
            ]);
    }
}
