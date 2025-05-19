<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the main organization for the application
        Organization::create([
            'name' => 'Foxtrot Organization',
            'type' => 'enterprise',
        ]);

        // Create additional organizations
        Organization::factory()->count(2)->create();
    }
}
