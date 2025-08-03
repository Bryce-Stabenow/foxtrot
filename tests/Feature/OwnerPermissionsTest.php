<?php

use App\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can view organization members', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);

    $response = $this->actingAs($owner)
        ->get(route('organization.members.index'));

    $response->assertStatus(200);
});

test('owner can view member details', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($owner)
        ->get(route('organization.members.show', $member));

    $response->assertStatus(200);
});

test('owner can update member role to admin', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($owner)
        ->patch(route('organization.members.update-role', $member), [
            'user_type' => 'admin',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member role updated successfully.');

    $member->refresh();
    expect($member->user_type)->toBe(UserType::ADMIN);
});

test('owner can update member role to owner', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($owner)
        ->patch(route('organization.members.update-role', $member), [
            'user_type' => 'owner',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member role updated successfully.');

    $member->refresh();
    expect($member->user_type)->toBe(UserType::OWNER);
});

test('owner can assign members to teams', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);
    $team = Team::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $response = $this->actingAs($owner)
        ->post(route('organization.members.assign-to-team', [
            'member' => $member->id,
            'team' => $team->id,
        ]));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member assigned to team successfully.');

    expect($member->teams)->toHaveCount(1);
    expect($member->teams->first()->id)->toBe($team->id);
});

test('owner can remove members from teams', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);
    $team = Team::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $member->teams()->attach($team->id);

    $response = $this->actingAs($owner)
        ->delete(route('organization.members.remove-from-team', [
            'member' => $member->id,
            'team' => $team->id,
        ]));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member removed from team successfully.');

    expect($member->teams)->toHaveCount(0);
});

test('owner can delete members', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    $response = $this->actingAs($owner)
        ->delete(route('organization.members.destroy', $member));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member deleted successfully.');

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
});

test('owner can send invitations', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::OWNER,
    ]);

    $response = $this->actingAs($owner)
        ->post(route('invitations.store'), [
            'email' => 'newmember@example.com',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Invitation sent successfully!');
});

test('member cannot perform admin actions', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);
    $otherMember = User::factory()->create([
        'organization_id' => $organization->id,
        'user_type' => UserType::MEMBER,
    ]);

    // Test viewing members
    $response = $this->actingAs($member)
        ->get(route('organization.members.index'));
    $response->assertStatus(403);

    // Test updating roles
    $response = $this->actingAs($member)
        ->patch(route('organization.members.update-role', $otherMember), [
            'user_type' => 'admin',
        ]);
    $response->assertStatus(403);

    // Test sending invitations
    $response = $this->actingAs($member)
        ->post(route('invitations.store'), [
            'email' => 'newmember@example.com',
        ]);
    $response->assertStatus(403);
}); 