<?php

// TODO convert to Pest
namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Team;
use App\Enums\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationMemberShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_member_details(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'user_type' => UserType::ADMIN,
        ]);
        $member = User::factory()->create([
            'organization_id' => $organization->id,
            'user_type' => UserType::MEMBER,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('organization.members.show', $member));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Organization/MemberShow')
                ->has('member')
                ->has('teams')
                ->has('organization')
        );
    }

    public function test_non_admin_cannot_view_member_details(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->create([
            'organization_id' => $organization->id,
            'user_type' => UserType::MEMBER,
        ]);
        $otherMember = User::factory()->create([
            'organization_id' => $organization->id,
            'user_type' => UserType::MEMBER,
        ]);

        $response = $this->actingAs($member)
            ->get(route('organization.members.show', $otherMember));

        $response->assertStatus(403);
    }

    public function test_admin_cannot_view_member_from_different_organization(): void
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        
        $admin = User::factory()->create([
            'organization_id' => $organization1->id,
            'user_type' => UserType::ADMIN,
        ]);
        $member = User::factory()->create([
            'organization_id' => $organization2->id,
            'user_type' => UserType::MEMBER,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('organization.members.show', $member));

        $response->assertStatus(403);
    }

    public function test_member_show_page_displays_correct_data(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'user_type' => UserType::ADMIN,
        ]);
        $member = User::factory()->create([
            'organization_id' => $organization->id,
            'user_type' => UserType::MEMBER,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $team = Team::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $member->teams()->attach($team);

        $response = $this->actingAs($admin)
            ->get(route('organization.members.show', $member));

        $response->assertInertia(fn ($page) => 
            $page->where('member.name', 'John Doe')
                ->where('member.email', 'john@example.com')
                ->where('member.user_type', 'member')
                ->has('member.teams', 1)
        );
    }
} 