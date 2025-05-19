<?php

namespace Database\Seeders;

use App\Models\Organization;
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
        // Get the organization created by TeamSeeder
        $organization = Organization::where('name', 'Foxtrot Organization')->first();

        // Get the teams
        $alphaTeam = Team::where('name', 'Alpha Team')->first();
        $betaTeam = Team::where('name', 'Beta Team')->first();

        $users = collect();
        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'name' => fake()->name(),
                'email' => fake()->email(),
                'password' => Hash::make('password'),
                'organization_id' => $organization->id,
            ]);
            $users->push($user);
        }

        $testUser = User::where('email', 'test@example.com')->first();
        if ($testUser) {
            $testUser->teams()->attach($alphaTeam->id);
            $testUser->teams()->attach($betaTeam->id);
        }
        
        [$alphaTeamMembers, $betaTeamMembers] = $users->chunk(5);

        $alphaTeamMembers->each(function ($user) use ($alphaTeam) {
            $user->teams()->attach($alphaTeam->id);
        });

        $betaTeamMembers->each(function ($user) use ($betaTeam) {
            $user->teams()->attach($betaTeam->id);
        });
    }
}
