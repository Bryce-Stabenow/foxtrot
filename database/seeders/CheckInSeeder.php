<?php

namespace Database\Seeders;

use App\Enums\CheckInStatus;
use App\Models\CheckIn;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Enums\UserType;

class CheckInSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the Foxtrot organization specifically
        $organization = Organization::where('name', 'Foxtrot Organization')->first();

        if (!$organization) {
            throw new \Exception('Foxtrot Organization not found');
        }

        // Get the specific teams for Foxtrot organization
        $teams = Team::where('organization_id', $organization->id)->get();
        if ($teams->isEmpty()) {
            throw new \Exception('No teams found for Foxtrot Organization');
        }

        // Get the users for Foxtrot organization
        $users = User::where('organization_id', $organization->id)->get();
        if ($users->isEmpty()) {
            throw new \Exception('No users found for Foxtrot Organization');
        }

        // Create check-ins for each team
        foreach ($teams as $team) {
            $teamUsers = $users->take(3);
            $adminUser = $users->where('user_type', UserType::ADMIN)->first() ?? $users->first();

            // Create pending check-ins
            CheckIn::factory()->count(3)->create([
                'team_id' => $team->id,
                'assigned_user_id' => $teamUsers->random()->id,
                'created_by_user_id' => $adminUser->id,
                'status' => CheckInStatus::PENDING,
            ]);

            // Create in-progress check-ins
            CheckIn::factory()->count(2)->inProgress()->create([
                'team_id' => $team->id,
                'assigned_user_id' => $teamUsers->random()->id,
                'created_by_user_id' => $adminUser->id,
            ]);

            // Create completed check-ins
            CheckIn::factory()->count(4)->completed()->create([
                'team_id' => $team->id,
                'assigned_user_id' => $teamUsers->random()->id,
                'created_by_user_id' => $adminUser->id,
            ]);

            // Create overdue check-ins
            CheckIn::factory()->count(2)->overdue()->create([
                'team_id' => $team->id,
                'assigned_user_id' => $teamUsers->random()->id,
                'created_by_user_id' => $adminUser->id,
            ]);

            // Create check-ins due soon
            CheckIn::factory()->count(3)->dueSoon()->create([
                'team_id' => $team->id,
                'assigned_user_id' => $teamUsers->random()->id,
                'created_by_user_id' => $adminUser->id,
            ]);

            // Create check-ins due today
            CheckIn::factory()->count(1)->dueToday()->create([
                'team_id' => $team->id,
                'assigned_user_id' => $teamUsers->random()->id,
                'created_by_user_id' => $adminUser->id,
            ]);
        }
    }
} 