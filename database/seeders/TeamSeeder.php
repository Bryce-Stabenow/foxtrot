<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the main organization
        $organization = Organization::where('name', 'Foxtrot Organization')->first();

        // Create Alpha Team
        Team::create([
            'name' => 'Alpha Team',
            'organization_id' => $organization->id,
        ]);

        // Create Beta Team
        Team::create([
            'name' => 'Beta Team',
            'organization_id' => $organization->id,
        ]);
    }
}
