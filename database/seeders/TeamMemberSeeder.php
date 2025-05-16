<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeamMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users and teams
        $users = User::all();
        $teams = Team::all();

        // Get the main test user
        $mainUser = $users->firstWhere('email', 'test@example.com');

        // Assign main test user to both teams
        foreach ($teams as $team) {
            $mainUser->teams()->attach($team->id);
        }

        // Create additional users for each team if needed
        $additionalUsers = [
            ['name' => 'Team Alpha Member 1', 'email' => 'alpha1@example.com'],
            ['name' => 'Team Alpha Member 2', 'email' => 'alpha2@example.com'],
            ['name' => 'Team Alpha Member 3', 'email' => 'alpha3@example.com'],
            ['name' => 'Team Alpha Member 4', 'email' => 'alpha4@example.com'],
            ['name' => 'Team Alpha Member 5', 'email' => 'alpha5@example.com'],
            ['name' => 'Team Beta Member 1', 'email' => 'beta1@example.com'],
            ['name' => 'Team Beta Member 2', 'email' => 'beta2@example.com'],
            ['name' => 'Team Beta Member 3', 'email' => 'beta3@example.com'],
            ['name' => 'Team Beta Member 4', 'email' => 'beta4@example.com'],
            ['name' => 'Team Beta Member 5', 'email' => 'beta5@example.com'],
        ];

        foreach ($additionalUsers as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
            ]);
            $users->push($user);
        }

        // Assign members to teams
        $alphaTeam = $teams->firstWhere('name', 'Alpha Team');
        $betaTeam = $teams->firstWhere('name', 'Beta Team');

        // Assign Alpha Team members
        for ($i = 1; $i <= 5; $i++) {
            $user = $users->firstWhere('email', "alpha{$i}@example.com");
            $user->teams()->attach($alphaTeam->id);
        }

        // Assign Beta Team members
        for ($i = 1; $i <= 5; $i++) {
            $user = $users->firstWhere('email', "beta{$i}@example.com");
            $user->teams()->attach($betaTeam->id);
        }
    }
}
