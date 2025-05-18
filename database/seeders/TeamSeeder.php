<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            ['name' => 'Alpha Team'],
            ['name' => 'Beta Team'],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }
    }
}
