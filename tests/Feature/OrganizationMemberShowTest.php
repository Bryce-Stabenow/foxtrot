<?php

use App\Models\User;
use App\Models\Organization;
use App\Models\Team;
use App\Enums\UserType;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
});

test('admin can view member details', function () {
    $admin = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    $member = User::factory()->create([
        'organization_id' => $this->organization->id,
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
});

test('non admin cannot view member details', function () {
    $member = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::MEMBER,
    ]);
    $otherMember = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($member)
        ->get(route('organization.members.show', $otherMember));

    $response->assertStatus(403);
});

test('admin cannot view member from different organization', function () {
    $organization2 = Organization::factory()->create();
    
    $admin = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization2->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('organization.members.show', $member));

    $response->assertStatus(403);
});

test('member show page displays correct data', function () {
    $admin = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::ADMIN,
    ]);
    $member = User::factory()->create([
        'organization_id' => $this->organization->id,
        'user_type' => UserType::MEMBER,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    $team = Team::factory()->create([
        'organization_id' => $this->organization->id,
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
}); 